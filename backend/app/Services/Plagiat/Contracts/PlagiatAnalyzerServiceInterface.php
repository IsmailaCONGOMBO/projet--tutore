<?php

namespace App\Services\Plagiat\Contracts;

interface PlagiatAnalyzerServiceInterface
{
    /**
     * Analyse un fichier PDF pour détecter le plagiat.
     * Orchestre tout le pipeline d'analyse.
     *
     * @param string $pdfPath Chemin vers le fichier PDF
     * @param bool $isTest Si true, le résultat n'est pas sauvegardé en base
     * @param int|null $excludeRapportId ID du rapport à exclure du corpus
     * @return array Rapport d'analyse structuré
     */
    public function analyze(string $pdfPath, bool $isTest = false, ?int $excludeRapportId = null): array;
}
