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
     * @param int|null $excludeRapportId ID du rapport à exclure du corpus (cas d'une ré-analyse)
     * @return array
     */
    public function buildCorpusFromDatabase(?int $excludeRapportId = null): array;

    /**
     * @param array $tokens
     * @param array $vocabulary
     * @param array $globalIDF
     * @return array
     */
    public function computeTFIDFWithVocabulary(array $tokens, array $vocabulary, array $globalIDF): array;

    /**
     * @param array $corpus
     * @param array $newDocTokens
     * @return array
     */
    public function buildGlobalVocabulary(array $corpus, array $newDocTokens): array;

    /**
     * @param array $vocabulary
     * @param array $corpus
     * @param array $newDocTokens
     * @return array
     */
    public function computeGlobalIDF(array $vocabulary, array $corpus, array $newDocTokens): array;
}
