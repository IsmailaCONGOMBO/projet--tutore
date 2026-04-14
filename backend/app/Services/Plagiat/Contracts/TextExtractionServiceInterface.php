<?php

namespace App\Services\Plagiat\Contracts;

interface TextExtractionServiceInterface
{
    /**
     * Extrait le texte d'un fichier PDF, page par page.
     *
     * @param string $pdfPath Le chemin absolu vers le fichier PDF
     * @return array Tableau associatif avec le numéro de page et le texte ['page' => N, 'text' => '...']
     * @throws \Exception Si le PDF est corrompu ou illisible
     */
    public function extractText(string $pdfPath): array;
}
