<?php

namespace App\Jobs;

use App\Models\Rapport;
use App\Services\Plagiat\Contracts\PlagiatAnalyzerServiceInterface;
use App\Services\Plagiat\Contracts\PlagiatReportServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyseRapportPlagiatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rapportId;

    /**
     * Create a new job instance.
     */
    public function __construct($rapportId)
    {
        $this->rapportId = $rapportId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        PlagiatAnalyzerServiceInterface $analyzer,
        PlagiatReportServiceInterface $reporter
    ): void {
        $rapport = Rapport::find($this->rapportId);
        
        if (!$rapport) {
            Log::error("AnalyseRapportPlagiatJob: Rapport $this->rapportId introuvable.");
            return;
        }

        try {
            Log::info("AnalyseRapportPlagiatJob: Début du traitement pour le rapport $this->rapportId.");

            // Le fichier est supposé se trouver dans storage/app/public/...
            // Ajuster le chemin selon la structure réelle de stockage
            $filePath = storage_path('app/public/' . $rapport->fichier_path);
            
            if (!file_exists($filePath)) {
                // Essayer sans le préfixe public si nécessaire
                $filePath = storage_path('app/' . $rapport->fichier_path);
            }

            if (!file_exists($filePath)) {
                throw new \Exception("Fichier PDF introuvable : " . $rapport->fichier_path);
            }

            // 1. Lancer l'analyse
            $result = $analyzer->analyze($filePath, false);

            // 2. Sauvegarder le résultat
            $reporter->saveAnalysis($this->rapportId, $result);

            // 3. Mettre à jour le taux global sur le modèle Rapport lui-même pour un accès rapide
            $rapport->update([
                'taux_plagiat' => $result['taux_global'],
                'date_analyse' => now(),
                'statut' => $result['decision'] === 'accepte' ? 'VALIDE' : 'REJETE'
            ]);

            Log::info("AnalyseRapportPlagiatJob: Analyse terminée pour le rapport $this->rapportId. Taux: " . $result['taux_global'] . "%");

            // Optionnel: Déclencher un événement pour notifier le frontend ou l'étudiant
            // event(new \App\Events\AnalyseCompleted($rapport));

        } catch (\Exception $e) {
            Log::error("AnalyseRapportPlagiatJob: Erreur pour le rapport $this->rapportId - " . $e->getMessage());
            
            $rapport->update([
                'statut' => 'ERREUR_ANALYSE'
            ]);
        }
    }
}
