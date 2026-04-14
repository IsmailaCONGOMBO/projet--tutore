<?php

namespace App\Services\Plagiat\Contracts;

interface TFIDFServiceInterface
{
    /**
     * Calcule le Term Frequency (TF) pour un document donné (tableau de tokens).
     *
     * @param array $tokens
     * @return array
     */
    public function computeTF(array $tokens): array;

    /**
     * Calcule l'Inverse Document Frequency (IDF) d'un terme dans un corpus.
     *
     * @param string $term
     * @param array $corpus
     * @return float
     */
    public function computeIDF(string $term, array $corpus): float;

    /**
     * Calcule le vecteur TF-IDF pour un document donné par rapport à un corpus.
     *
     * @param array $tokens
     * @param array $corpus
     * @return array
     */
    public function computeTFIDF(array $tokens, array $corpus): array;

    /**
     * Construit le corpus à partir de la base de données.
     *
     * @return array
     */
    public function buildCorpusFromDatabase(): array;
}
