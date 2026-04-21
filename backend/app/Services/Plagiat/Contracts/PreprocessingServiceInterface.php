<?php

namespace App\Services\Plagiat\Contracts;

interface PreprocessingServiceInterface
{
    /**
     * Nettoie, tokenize et stemmise le texte brut.
     *
     * @param string $text Le texte brut
     * @return array Tableau de tokens (mots) nettoyés et stemmisés
     */
    public function tokenizeAndStem(string $text): array;

    /**
     * Retourne le texte nettoyé et stemmisé sous forme de chaîne de caractères.
     *
     * @param string $text Le texte brut
     * @return string Le texte prétraité
     */
    public function preprocessText(string $text): string;

    /**
     * Génère un hash unique du texte nettoyé.
     *
     * @param string $text
     * @return string
     */
    public function generateHash(string $text): string;
}
