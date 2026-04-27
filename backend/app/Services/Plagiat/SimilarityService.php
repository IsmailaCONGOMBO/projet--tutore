<?php

namespace App\Services\Plagiat;

use App\Services\Plagiat\Contracts\SimilarityServiceInterface;

class SimilarityService implements SimilarityServiceInterface
{
    /**
     * @inheritDoc
     */
    public function cosineSimilarity(array $vecA, array $vecB): float
    {
        // Obtenir tous les termes uniques des deux vecteurs
        $allTerms = array_unique(array_merge(array_keys($vecA), array_keys($vecB)));

        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        foreach ($allTerms as $term) {
            $valA = isset($vecA[$term]) ? $vecA[$term] : 0;
            $valB = isset($vecB[$term]) ? $vecB[$term] : 0;

            $dotProduct += ($valA * $valB);
            $normA += ($valA * $valA);
            $normB += ($valB * $valB);
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        // Éviter la division par zéro
        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        $similarity = $dotProduct / ($normA * $normB);

        // Gérer les imprécisions flottantes (ex: 1.0000000000002)
        if ($similarity > 1.0) {
            return 1.0;
        }

        return $similarity;
    }

    /**
     * @inheritDoc
     */
    public function jaccardSimilarity(array $tokensA, array $tokensB): float
    {
        // Utiliser des ensembles uniques pour Jaccard
        $setA = array_unique($tokensA);
        $setB = array_unique($tokensB);

        $intersection = array_intersect($setA, $setB);
        $union = array_unique(array_merge($setA, $setB));

        $countUnion = count($union);

        // Si l'union est vide, les deux ensembles sont vides
        if ($countUnion === 0) {
            return 0.0;
        }

        return count($intersection) / $countUnion;
    }

    /**
     * Similarité de recouvrement (Overlap Coefficient).
     * Calcule quelle proportion du plus petit document est contenue dans le plus grand.
     * Très efficace pour détecter le plagiat d'un chapitre entier.
     */
    public function overlapSimilarity(array $tokensA, array $tokensB): float
    {
        $setA = array_unique($tokensA);
        $setB = array_unique($tokensB);

        $intersection = array_intersect($setA, $setB);
        $minSize = min(count($setA), count($setB));

        if ($minSize === 0) {
            return 0.0;
        }

        return count($intersection) / $minSize;
    }
}
