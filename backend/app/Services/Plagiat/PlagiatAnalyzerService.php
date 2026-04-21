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
    public function analyze(string $pdfPath, bool $isTest = false, ?int $excludeRapportId = null): array
    {
        Log::info("PlagiatAnalyzerService: Début de l'analyse pour $pdfPath (is_test=" . ($isTest ? 'true' : 'false') . ", exclude=$excludeRapportId)");

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
        Log::info("PlagiatAnalyzerService: Construction du corpus depuis la base de données (exclusion ID: $excludeRapportId).");
        $corpus = $this->tfidf->buildCorpusFromDatabase($excludeRapportId);
        
        // Si le corpus est vide, on arrête ici (taux de plagiat 0%)
        if (empty($corpus)) {
            Log::info("PlagiatAnalyzerService: Corpus vide. Taux de plagiat 0% par défaut.");
            return [
                'taux_global' => 0.0,
                'decision' => 'accepte',
                'segments' => [],
                'is_test' => $isTest,
                'analyzed_at' => now()->format('Y-m-d H:i:s')
            ];
        }

        // Extraire les tokens de chaque doc du corpus pour TFIDF
        $corpusTokensList = array_column($corpus, 'tokens');

        $segmentsResult = [];
        $totalWordCount = 0;
        $weightedScoreSum = 0;

        // 4. Analyser chaque segment
        foreach ($segments as $segment) {
            $segmentLabel = $segment['label'];
            $segmentText = $segment['text'];
            
            // a. Préprocesser et Hacher (Fast Match)
            $segmentTokens = $this->preprocessor->tokenizeAndStem($segmentText);
            $segmentHash = $this->preprocessor->generateHash($segmentText);
            $segmentWordCount = count($segmentTokens);
            
            if ($segmentWordCount === 0) {
                // Segment vide
                $segmentsResult[] = [
                    'label' => $segmentLabel,
                    'text' => $segmentText,
                    'hash' => $segmentHash,
                    'taux' => 0.0,
                    'nb_mots' => 0,
                    'doc_similaire' => null,
                    'scores_detail' => ['cosinus' => 0.0, 'jaccard' => 0.0, 'ngram' => 0.0]
                ];
                continue;
            }

            // --- EXPERT : RECHERCHE DE CORRESPONDANCE EXACTE (HASH) ---
            $isExactMatch = false;
            foreach ($corpus as $doc) {
                if (isset($doc['hash']) && $doc['hash'] === $segmentHash) {
                    Log::debug("PlagiatAnalyzerService: Match exact détecté (Hash) pour le segment $segmentLabel avec " . $doc['doc_name']);
                    $segmentsResult[] = [
                        'label' => $segmentLabel,
                        'text' => $segmentText,
                        'hash' => $segmentHash,
                        'taux' => 100.0,
                        'nb_mots' => $segmentWordCount,
                        'doc_similaire' => $doc['doc_name'],
                        'scores_detail' => ['cosinus' => 1.0, 'jaccard' => 1.0, 'ngram' => 1.0]
                    ];
                    $totalWordCount += $segmentWordCount;
                    $weightedScoreSum += (100.0 * $segmentWordCount);
                    $isExactMatch = true;
                    break;
                }
            }

            if ($isExactMatch) continue;

            // --- ANALYSE STATISTIQUE CLASSIQUE ---

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
                if (count($docTokens) === 0) continue;

                $docTfidf = $this->tfidf->computeTFIDF($docTokens, $corpusTokensList);

                // Calculer les 3 scores
                $cosineScore = $this->similarity->cosineSimilarity($segmentTfidf, $docTfidf);
                $jaccardScore = $this->similarity->jaccardSimilarity($segmentTokens, $docTokens);
                $ngramScore = $this->paraphrase->ngramSimilarity($segmentTokens, $docTokens, 3);

                // Formule pondérée EXPERT : (0.6 * cos) + (0.1 * jaccard) + (0.3 * ngram)
                // On privilégie le Cosinus (sémantique/lexical) et les N-grams (séquences)
                $globalScore = (0.60 * $cosineScore) + (0.10 * $jaccardScore) + (0.30 * $ngramScore);

                Log::debug("PlagiatAnalyzerService: Comparaison $segmentLabel vs " . $doc['doc_name'] . " - Sc: " . round($globalScore, 4) . " (Cos: " . round($cosineScore, 4) . ", Jac: " . round($jaccardScore, 4) . ", Ngr: " . round($ngramScore, 4) . ")");

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
                'text' => $segmentText,
                'hash' => $segmentHash,
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
