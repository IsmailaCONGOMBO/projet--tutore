<?php

namespace App\Services\Plagiat;

use App\Services\Plagiat\Contracts\ParaphraseDetectionServiceInterface;
use App\Services\Plagiat\Contracts\SimilarityServiceInterface;

class ParaphraseDetectionService implements ParaphraseDetectionServiceInterface
{
    /**
     * @var SimilarityServiceInterface
     */
    protected $similarityService;

    public function __construct(SimilarityServiceInterface $similarityService)
    {
        $this->similarityService = $similarityService;
    }

    /**
     * @inheritDoc
     */
    public function generateNgrams(array $tokens, int $n): array
    {
        $ngrams = [];
        $totalTokens = count($tokens);

        if ($totalTokens < $n) {
            // Si pas assez de tokens pour un n-gramme complet, retourner tout le texte comme 1 gramme (si possible)
            if ($totalTokens > 0) {
                return [implode(' ', $tokens)];
            }
            return [];
        }

        // Fenêtre glissante pour créer les n-grammes
        for ($i = 0; $i <= $totalTokens - $n; $i++) {
            $ngram = array_slice($tokens, $i, $n);
            $ngrams[] = implode(' ', $ngram);
        }

        return $ngrams;
    }

    /**
     * @inheritDoc
     */
    public function ngramSimilarity(array $tokensA, array $tokensB, int $n = 3): float
    {
        $ngramsA = $this->generateNgrams($tokensA, $n);
        $ngramsB = $this->generateNgrams($tokensB, $n);

        // Utiliser la similarité de Jaccard sur les ensembles de n-grammes
        return $this->similarityService->jaccardSimilarity($ngramsA, $ngramsB);
    }
}
