<?php

namespace App\Services\Plagiat\Contracts;

interface ParaphraseDetectionServiceInterface
{
    /**
     * Génère tous les n-grammes (séquences de n tokens consécutifs) d'un texte tokenisé.
     *
     * @param array $tokens
     * @param int $n
     * @return array
     */
    public function generateNgrams(array $tokens, int $n): array;

    /**
     * Calcule la similarité basée sur les n-grammes entre deux ensembles de tokens.
     * Approximation lexicale par n-grammes pour détecter la paraphrase.
     *
     * @param array $tokensA
     * @param array $tokensB
     * @param int $n
     * @return float Score entre 0 et 1
     */
    public function ngramSimilarity(array $tokensA, array $tokensB, int $n = 3): float;
}
