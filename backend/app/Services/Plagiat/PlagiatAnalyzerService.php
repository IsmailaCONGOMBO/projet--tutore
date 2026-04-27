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
        $this->extractor    = $extractor;
        $this->segmenter    = $segmenter;
        $this->preprocessor = $preprocessor;
        $this->tfidf        = $tfidf;
        $this->similarity   = $similarity;
        $this->paraphrase   = $paraphrase;
    }

    /**
     * @inheritDoc
     */
    public function analyze(string $pdfPath, bool $isTest = false, ?int $excludeRapportId = null): array
    {
        Log::info("PlagiatAnalyzerService: Début analyse — $pdfPath (is_test=" . ($isTest ? 'true' : 'false') . ", exclude=$excludeRapportId)");

        // ──────────────────────────────────────────────────────────────────────
        // ÉTAPE 1 : Extraction du texte
        // ──────────────────────────────────────────────────────────────────────
        $pages    = $this->extractor->extractText($pdfPath);
        $fullText = '';
        foreach ($pages as $pageData) {
            $fullText .= $pageData['text'] . "\n";
        }

        Log::debug("PlagiatAnalyzerService: Texte extrait — " . mb_strlen($fullText) . " caractères.");

        // ──────────────────────────────────────────────────────────────────────
        // ÉTAPE 2 : Hash du rapport COMPLET (Fast-Match Database)
        // ──────────────────────────────────────────────────────────────────────
        // ✅ UTILISATION DU SERVICE DE PRÉTRAITEMENT POUR LE HASH (Source unique de vérité)
        $fullDocHash = $this->preprocessor->generateHash($fullText);
        
        Log::info("PlagiatAnalyzerService: [DEBUG HASH] Hash calculé = $fullDocHash");

        // ✅ RECHERCHE DIRECTE EN BASE DE DONNÉES (Ultra Rapide)
        $existingRapport = \App\Models\Rapport::where('hash_document', $fullDocHash)
            ->where('id', '!=', $excludeRapportId)
            ->first();

        if ($existingRapport) {
            Log::info("PlagiatAnalyzerService: ✅ MATCH HASH BDD détecté avec Rapport #{$existingRapport->id}");
            
            $tokens = $this->preprocessor->tokenizeAndStem($fullText);
            return $this->buildResult(100.0, 'EXACT_MATCH', [[
                'label'         => 'rapport_complet',
                'text'          => $fullText,
                'hash'          => $fullDocHash,
                'taux'          => 100.0,
                'nb_mots'       => count($tokens),
                'tokens_preprocessed' => $tokens, // ✅ Ajouté pour stockage cohérent
                'doc_similaire' => $existingRapport->titre ?? "Rapport #{$existingRapport->id}",
                'scores_detail' => ['cosinus' => 1.0, 'jaccard' => 1.0, 'ngram' => 1.0],
            ]], $isTest, $fullDocHash);
        }

        // ──────────────────────────────────────────────────────────────────────
        // ÉTAPE 3 : Construction du corpus (si pas de match hash direct)
        // ──────────────────────────────────────────────────────────────────────
        $corpus = $this->tfidf->buildCorpusFromDatabase($excludeRapportId);

        if (empty($corpus)) {
            Log::info("PlagiatAnalyzerService: Corpus vide → taux 0%.");
            return $this->buildResult(0.0, 'DIFFERENT', [], $isTest, $fullDocHash);
        }

        // Séparer les entrées rapport-complet des entrées chapitre
        $corpusRapports = array_filter($corpus, fn($d) => ($d['granularity'] ?? '') === 'rapport');
        $corpusChapitres = array_filter($corpus, fn($d) => ($d['granularity'] ?? '') === 'chapitre');

        // Liste de tous les tokens (pour IDF global)
        $allCorpusTokensList = array_column($corpus, 'tokens');

        // ──────────────────────────────────────────────────────────────────────
        // ÉTAPE 4 : FAST-MATCH NIVEAU RAPPORT (hash du rapport entier)
        // ──────────────────────────────────────────────────────────────────────
        foreach ($corpusRapports as $doc) {
            if (!empty($doc['hash']) && $doc['hash'] === $fullDocHash) {
                Log::info("PlagiatAnalyzerService: ✅ FAST-MATCH RAPPORT COMPLET détecté avec " . $doc['doc_name']);

                $fullTokens = $this->preprocessor->tokenizeAndStem($fullText);
                $wordCount  = count($fullTokens);

                return $this->buildResult(100.0, 'rejete', [[
                    'label'         => 'rapport_complet',
                    'text'          => $fullText,
                    'hash'          => $fullDocHash,
                    'taux'          => 100.0,
                    'nb_mots'       => $wordCount,
                    'tokens_preprocessed' => $fullTokens, // ✅ Ajouté pour stockage cohérent
                    'doc_similaire' => $doc['doc_name'],
                    'scores_detail' => ['cosinus' => 1.0, 'jaccard' => 1.0, 'ngram' => 1.0],
                ]], $isTest, $fullDocHash);
            }
        }

        // ──────────────────────────────────────────────────────────────────────
        // ÉTAPE 5 : Segmentation
        // ──────────────────────────────────────────────────────────────────────
        $segments = $this->segmenter->segment($fullText);
        Log::info("PlagiatAnalyzerService: " . count($segments) . " segment(s) détecté(s).");

        // ✅ EXPERT NLP : Pré-calcul du Vocabulaire et de l'IDF Globaux pour TOUTE l'analyse
        // Cela garantit la cohérence des vecteurs entre toutes les comparaisons.
        $allSegmentsTokens = [];
        foreach ($segments as $s) {
            $allSegmentsTokens = array_merge($allSegmentsTokens, $this->preprocessor->tokenizeAndStem($s['text']));
        }
        $globalVocabulary = $this->tfidf->buildGlobalVocabulary($corpus, $allSegmentsTokens);
        $globalIDF = $this->tfidf->computeGlobalIDF($globalVocabulary, $corpus, $allSegmentsTokens);

        // ✅ FIX CRITIQUE : Comparer contre TOUT le corpus (Diagnostic 6)
        $targetCorpus = $corpus;

        // ✅ EXPERT NLP : Pré-calculer les vecteurs TF-IDF pour tout le corpus
        // Cela évite de recalculer les vecteurs des documents archivés pour chaque segment.
        $corpusTfidfVectors = [];
        foreach ($targetCorpus as $index => $doc) {
            $corpusTfidfVectors[$index] = $this->tfidf->computeTFIDFWithVocabulary(
                $doc['tokens'],
                $globalVocabulary,
                $globalIDF
            );
        }

        $segmentsResult  = [];
        $totalWordCount  = 0;
        $weightedScoreSum = 0;

        // ──────────────────────────────────────────────────────────────────────
        // ÉTAPE 6 : Analyse de chaque segment
        // ──────────────────────────────────────────────────────────────────────
        foreach ($segments as $segmentIndex => $segment) {
            $segmentLabel = $segment['label'];
            $segmentText  = $segment['text'];

            // Tokens et hash du segment
            $segmentTokens    = $this->preprocessor->tokenizeAndStem($segmentText);
            $segmentHash      = $this->preprocessor->generateHash($segmentText);
            $segmentWordCount = count($segmentTokens);

            Log::info("PlagiatAnalyzerService: Analyse du segment " . ($segmentIndex+1) . "/" . count($segments) . " ($segmentLabel)");

            if ($segmentWordCount === 0) {
                $segmentsResult[] = $this->emptySegmentResult($segmentLabel, $segmentText, $segmentHash);
                continue;
            }

            // ── FAST-MATCH NIVEAU SEGMENT (hash) ─────────────────────────────
            $isExactMatch = false;

            foreach ($targetCorpus as $doc) {
                if (!empty($doc['hash']) && $doc['hash'] === $segmentHash) {
                    Log::info("PlagiatAnalyzerService: ✅ FAST-MATCH SEGMENT '$segmentLabel' avec " . $doc['doc_name']);
                    $segmentsResult[] = [
                        'label'         => $segmentLabel,
                        'text'          => $segmentText,
                        'hash'          => $segmentHash,
                        'taux'          => 100.0,
                        'nb_mots'       => $segmentWordCount,
                        'tokens_preprocessed' => $segmentTokens, // ✅ Ajouté pour stockage cohérent
                        'doc_similaire' => $doc['doc_name'],
                        'scores_detail' => ['cosinus' => 1.0, 'jaccard' => 1.0, 'ngram' => 1.0],
                    ];
                    $totalWordCount   += $segmentWordCount;
                    $weightedScoreSum += (100.0 * $segmentWordCount);
                    $isExactMatch = true;
                    break;
                }
            }

            if ($isExactMatch) {
                continue;
            }

            // ── ANALYSE STATISTIQUE ───────────────────────────────────────────
            $maxScore       = 0.0;
            $bestDocName    = null;
            $bestScoresDetail = ['cosinus' => 0.0, 'jaccard' => 0.0, 'ngram' => 0.0];

            // ✅ EXPERT NLP : Pré-calculer le vecteur TF-IDF du segment une seule fois
            $segmentTfidf = $this->tfidf->computeTFIDFWithVocabulary(
                $segmentTokens,
                $globalVocabulary,
                $globalIDF
            );

            foreach ($targetCorpus as $docIndex => $doc) {
                $docTokens = $doc['tokens'];
                if (count($docTokens) === 0) {
                    continue;
                }

                $docTfidf = $corpusTfidfVectors[$docIndex];

                $cosineScore   = $this->similarity->cosineSimilarity($segmentTfidf, $docTfidf);
                $jaccardScore  = $this->similarity->jaccardSimilarity($segmentTokens, $docTokens);
                $ngramScore    = $this->paraphrase->ngramSimilarity($segmentTokens, $docTokens, 3);
                $overlapScore  = $this->similarity->overlapSimilarity($segmentTokens, $docTokens);

                // ✅ Formule pondérée EXPERT (70/20/10)
                // On garde l'Overlap comme garde-fou pour les documents inclus
                $globalScore = (0.70 * $cosineScore) + (0.20 * $jaccardScore) + (0.10 * $ngramScore);

                // Si le recouvrement ou le cosinus est total, on force le score
                if ($overlapScore > 0.98 || $cosineScore > 0.98) {
                    $globalScore = max($globalScore, $overlapScore, $cosineScore);
                }

                Log::info(sprintf(
                    "PlagiatAnalyzerService: [SCORES DETAIL] %s vs %s — Global: %.2f%% (Cos: %.4f, Jac: %.4f, Ngr: %.4f, Ovlp: %.4f)",
                    $segmentLabel,
                    $doc['doc_name'],
                    $globalScore * 100,
                    $cosineScore,
                    $jaccardScore,
                    $ngramScore,
                    $overlapScore
                ));

                if ($globalScore > $maxScore) {
                    $maxScore     = $globalScore;
                    $bestDocName  = $doc['doc_name'];
                    $bestScoresDetail = [
                        'cosinus' => round($cosineScore, 4),
                        'jaccard' => round($jaccardScore, 4),
                        'ngram'   => round($ngramScore, 4),
                    ];
                }
            }

            $tauxPourcentage = round($maxScore * 100, 2);

            Log::info("PlagiatAnalyzerService: Segment '$segmentLabel' → taux={$tauxPourcentage}%, source={$bestDocName}");

            $segmentsResult[] = [
                'label'         => $segmentLabel,
                'text'          => $segmentText,
                'hash'          => $segmentHash,
                'taux'          => $tauxPourcentage,
                'nb_mots'       => $segmentWordCount,
                'tokens_preprocessed' => $segmentTokens, // ✅ Ajouté pour stockage cohérent
                'doc_similaire' => $bestDocName,
                'scores_detail' => $bestScoresDetail,
            ];

            $totalWordCount   += $segmentWordCount;
            $weightedScoreSum += ($tauxPourcentage * $segmentWordCount);
        }

        // ──────────────────────────────────────────────────────────────────────
        // ÉTAPE 7 : Calcul du taux global (pondéré par le nombre de mots)
        // ──────────────────────────────────────────────────────────────────────
        $tauxGlobal = 0.0;
        if ($totalWordCount > 0) {
            $tauxGlobal = round($weightedScoreSum / $totalWordCount, 2);
        }

        // ✅ INDICATEUR DE STATUT (Expert)
        $decision = 'DIFFERENT';
        if ($tauxGlobal >= 98.0) {
            $decision = 'EXACT_MATCH';
        } elseif ($tauxGlobal >= 20.0) {
            $decision = 'SIMILAR';
        }

        Log::info("PlagiatAnalyzerService: ✅ Analyse terminée — Taux global: {$tauxGlobal}% / Statut: {$decision}");

        return $this->buildResult($tauxGlobal, $decision, $segmentsResult, $isTest, $fullDocHash);
    }

    /**
     * Construit le tableau de résultat final.
     */
    private function buildResult(float $tauxGlobal, string $decision, array $segments, bool $isTest, ?string $hash = null): array
    {
        return [
            'taux_global'   => $tauxGlobal,
            'decision'      => $decision,
            'hash_document' => $hash,
            'segments'      => $segments,
            'is_test'       => $isTest,
            'analyzed_at'   => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Retourne un résultat vide pour un segment sans tokens.
     */
    private function emptySegmentResult(string $label, string $text, string $hash): array
    {
        return [
            'label'         => $label,
            'text'          => $text,
            'hash'          => $hash,
            'taux'          => 0.0,
            'nb_mots'       => 0,
            'tokens_preprocessed' => [], // ✅ Ajouté pour stockage cohérent
            'doc_similaire' => null,
            'scores_detail' => ['cosinus' => 0.0, 'jaccard' => 0.0, 'ngram' => 0.0],
        ];
    }
}
