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
}
