<?php

namespace App\Http\Controllers;

use App\Models\Rapport;
use App\Models\AnalysePlagiat;
use App\Services\Plagiat\Contracts\PlagiatAnalyzerServiceInterface;
use App\Services\Plagiat\Contracts\PlagiatReportServiceInterface;
use App\Jobs\AnalyseRapportPlagiatJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PlagiatController extends Controller
{
    protected $analyzer;
    protected $reporter;

    public function __construct(
        PlagiatAnalyzerServiceInterface $analyzer,
        PlagiatReportServiceInterface $reporter
    ) {
        $this->analyzer = $analyzer;
        $this->reporter = $reporter;
    }

    /**
     * POST /api/rapports/{id}/analyser
     * Lance l'analyse officielle (is_test = false)
     * Utilise un Job Laravel pour ne pas bloquer la requête HTTP.
     */
    public function analyser($id)
    {
        try {
            $rapport = Rapport::findOrFail($id);
            
            $filePath = storage_path('app/public/' . $rapport->fichier_path);
            
            // Si pas dans public, essayer dans storage/app
            if (!file_exists($filePath)) {
                $filePath = storage_path('app/' . $rapport->fichier_path);
            }

            if (!file_exists($filePath)) {
                return response()->json(['message' => 'Fichier PDF du rapport introuvable.'], 404);
            }

            // Mettre à jour l'état du rapport
            $rapport->statut = 'EN_ANALYSE';
            $rapport->save();

            // Dispatch du Job pour traitement asynchrone (ne pas bloquer)
            AnalyseRapportPlagiatJob::dispatch($id);

            Log::info("PlagiatController: Job d'analyse envoyé pour le rapport $id.");

            return response()->json([
                'message' => 'Analyse lancée avec succès en arrière-plan.',
                'rapport_id' => $id,
                'statut' => 'EN_ANALYSE'
            ], 202); // 202 Accepted

        } catch (\Exception $e) {
            Log::error("Erreur lors du lancement de l'analyse du rapport $id : " . $e->getMessage());
            return response()->json(['message' => 'Erreur lors du lancement de l\'analyse.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/rapports/tester
     * Lance l'analyse en mode test (is_test = true)
     * Retourne le résultat immédiatement pour l'utilisateur.
     */
    public function tester(Request $request)
    {
        try {
            $request->validate([
                'fichier' => 'required|mimes:pdf|max:20480', // 20 MB max
            ]);

            $file = $request->file('fichier');
            $tempPath = $file->store('temp_tests');
            $fullPath = storage_path('app/' . $tempPath);

            Log::info("PlagiatController: Début de l'analyse en mode test.");

            // Analyse synchrone pour feedback immédiat
            $result = $this->analyzer->analyze($fullPath, true);
            $reportHtml = $this->reporter->generateHumanReadableReport($result);

            // Supprimer le fichier temporaire
            Storage::delete($tempPath);

            return response()->json([
                'message' => 'Analyse de test terminée.',
                'result' => $result,
                'html_report' => $reportHtml
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'analyse de test : " . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'analyse de test.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/rapports/{id}/analyse-resultat
     * Permet de récupérer le résultat de la dernière analyse officielle.
     */
    public function getResultat($id)
    {
        $rapport = Rapport::with('analyse')->findOrFail($id);
        
        if (!$rapport->analyse) {
            return response()->json(['message' => 'Aucune analyse trouvée pour ce rapport.'], 404);
        }

        $payload = json_decode($rapport->analyse->payload_json, true);
        $htmlReport = $this->reporter->generateHumanReadableReport($payload);

        return response()->json([
            'rapport' => $rapport,
            'analyse' => $rapport->analyse,
            'html_report' => $htmlReport
        ]);
    }
}
