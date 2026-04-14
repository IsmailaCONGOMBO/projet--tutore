<?php

namespace App\Services\Plagiat;

use App\Services\Plagiat\Contracts\DocumentSegmentationServiceInterface;
use App\Services\Plagiat\Contracts\ParaphraseDetectionServiceInterface;
use App\Services\Plagiat\Contracts\PlagiatAnalyzerServiceInterface;
use App\Services\Plagiat\Contracts\PreprocessingServiceInterface;
use App\Services\Plagiat\Contracts\SimilarityServiceInterface;
use App\Services\Plagiat\Contracts\TextExtractionServiceInterface;
use App\Services\Plagiat\Contracts\TFIDFServiceInterface;
use Illuminate\Support\Facades\Log;

class PlagiatAnalyzerService implements PlagiatAnalyzerServiceInterface
{
    protected $extractor;
    protected $segmenter;
    protected $preprocessor;
    protected $tfidf;
    protected $similarity;
    protected $paraphrase;

    public function __construct(
        TextExtractionServiceInterface $extractor,
        DocumentSegmentationServiceInterface $segmenter,
        PreprocessingServiceInterface $preprocessor,
        TFIDFServiceInterface $tfidf,
        SimilarityServiceInterface $similarity,
        ParaphraseDetectionServiceInterface $paraphrase
    ) {
        $this->extractor = $extractor;
        $this->segmenter = $segmenter;
        $this->preprocessor = $preprocessor;
        $this->tfidf = $tfidf;
        $this->similarity = $similarity;
        $this->paraphrase = $paraphrase;
    }

    /**
     * @inheritDoc
     */
    public function analyze(string $pdfPath, bool $isTest = false): array
    {
        Log::info("PlagiatAnalyzerService: Début de l'analyse pour $pdfPath (is_test=" . ($isTest ? 'true' : 'false') . ")");

        // 1. Extraire le texte
        $pages = $this->extractor->extractText($pdfPath);

        // 2. Concaténer toutes les pages
        $fullText = '';
        foreach ($pages as $pageData) {
            $fullText .= $pageData['text'] . "\n";
        }

        // 3. Segmenter en chapitres (ou rapport complet)
        $segments = $this->segmenter->segment($fullText);

        // Préparer le corpus
        // En conditions réelles, le chargement du corpus devrait être optimisé ou mis en cache
        Log::info("PlagiatAnalyzerService: Construction du corpus depuis la base de données.");
        $corpus = $this->tfidf->buildCorpusFromDatabase();
        
        // Extraire les tokens de chaque doc du corpus pour TFIDF
        $corpusTokensList = array_column($corpus, 'tokens');

        $segmentsResult = [];
        $totalWordCount = 0;
        $weightedScoreSum = 0;

        // 4. Analyser chaque segment
        foreach ($segments as $segment) {
            $segmentLabel = $segment['label'];
            $segmentText = $segment['text'];
            
            // a. Préprocesser
            $segmentTokens = $this->preprocessor->tokenizeAndStem($segmentText);
            $segmentWordCount = count($segmentTokens);
            
            if ($segmentWordCount === 0) {
                // Segment vide
                $segmentsResult[] = [
                    'label' => $segmentLabel,
                    'taux' => 0.0,
                    'nb_mots' => 0,
                    'doc_similaire' => null,
                    'scores_detail' => [
                        'cosinus' => 0.0,
                        'jaccard' => 0.0,
                        'ngram' => 0.0
                    ]
                ];
                continue;
            }

            // b. Vecteur TF-IDF du segment
            $segmentTfidf = $this->tfidf->computeTFIDF($segmentTokens, $corpusTokensList);

            $maxScore = 0;
            $bestDocName = null;
            $bestScoresDetail = [
                'cosinus' => 0.0,
                'jaccard' => 0.0,
                'ngram' => 0.0
            ];

            // c. Comparer contre chaque document du corpus
            foreach ($corpus as $index => $doc) {
                $docTokens = $doc['tokens'];
                
                // Si le document est vide, on ignore
                if (count($docTokens) === 0) continue;

                $docTfidf = $this->tfidf->computeTFIDF($docTokens, $corpusTokensList);

                // Calculer les 3 scores
                $cosineScore = $this->similarity->cosineSimilarity($segmentTfidf, $docTfidf);
                $jaccardScore = $this->similarity->jaccardSimilarity($segmentTokens, $docTokens);
                $ngramScore = $this->paraphrase->ngramSimilarity($segmentTokens, $docTokens, 3);

                // Formule pondérée : (0.5 * cos) + (0.3 * jaccard) + (0.2 * ngram)
                $globalScore = (0.50 * $cosineScore) + (0.30 * $jaccardScore) + (0.20 * $ngramScore);

                if ($globalScore > $maxScore) {
                    $maxScore = $globalScore;
                    $bestDocName = $doc['doc_name'];
                    $bestScoresDetail = [
                        'cosinus' => round($cosineScore, 4),
                        'jaccard' => round($jaccardScore, 4),
                        'ngram' => round($ngramScore, 4)
                    ];
                }
            }

            // d. Stocker le résultat du segment
            // Convertir le score max en pourcentage
            $tauxPourcentage = round($maxScore * 100, 2);

            $segmentsResult[] = [
                'label' => $segmentLabel,
                'taux' => $tauxPourcentage,
                'nb_mots' => $segmentWordCount,
                'doc_similaire' => $bestDocName,
                'scores_detail' => $bestScoresDetail
            ];

            $totalWordCount += $segmentWordCount;
            $weightedScoreSum += ($tauxPourcentage * $segmentWordCount);
        }

        // 5. Calculer le taux GLOBAL
        $tauxGlobal = 0;
        if ($totalWordCount > 0) {
            $tauxGlobal = round($weightedScoreSum / $totalWordCount, 2);
        }

        $decision = ($tauxGlobal > 20.0) ? 'rejete' : 'accepte';

        Log::info("PlagiatAnalyzerService: Analyse terminée. Taux global: $tauxGlobal% / Décision: $decision");

        // 6. Retourner le rapport d'analyse
        return [
            'taux_global' => $tauxGlobal,
            'decision' => $decision,
            'segments' => $segmentsResult,
            'is_test' => $isTest,
            'analyzed_at' => now()->format('Y-m-d H:i:s')
        ];
    }
}
