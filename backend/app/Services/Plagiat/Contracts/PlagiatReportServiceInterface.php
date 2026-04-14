<?php

namespace App\Services\Plagiat\Contracts;

use App\Models\AnalysePlagiat;

interface PlagiatReportServiceInterface
{
    /**
     * Persiste le résultat en base (modèle AnalysePlagiat et met à jour les chapitres).
     *
     * @param int $rapportId ID du rapport
     * @param array $analysisResult Résultat retourné par PlagiatAnalyzerService
     * @return AnalysePlagiat
     */
    public function saveAnalysis(int $rapportId, array $analysisResult): AnalysePlagiat;

    /**
     * Retourne une chaîne HTML (ou Markdown) lisible résumant l'analyse.
     *
     * @param array $analysisResult
     * @return string
     */
    public function generateHumanReadableReport(array $analysisResult): string;
}
