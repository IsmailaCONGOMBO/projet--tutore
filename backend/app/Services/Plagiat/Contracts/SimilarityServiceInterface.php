<?php

namespace App\Services\Plagiat\Contracts;

interface SimilarityServiceInterface
{
    /**
     * Calcule la similarité Cosinus entre deux vecteurs TF-IDF.
     *
     * @param array $vecA
     * @param array $vecB
     * @return float Score entre 0 et 1
     */
    public function cosineSimilarity(array $vecA, array $vecB): float;

    /**
     * Calcule la similarité de Jaccard entre deux ensembles de tokens.
     * Note: la similarité est calculée sur des ensembles uniques (array_unique).
     *
     * @param array $tokensA
     * @param array $tokensB
     * @return float Score entre 0 et 1
     */
    /**
     * Calcule la similarité de recouvrement (Overlap Coefficient).
     *
     * @param array $tokensA
     * @param array $tokensB
     * @return float Score entre 0 et 1
     */
    public function overlapSimilarity(array $tokensA, array $tokensB): float;
}
