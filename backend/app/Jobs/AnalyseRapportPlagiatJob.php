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

            // 1. Lancer l'analyse (en excluant le rapport courant du corpus)
            $result = $analyzer->analyze($filePath, false, $this->rapportId);

            // 2. Sauvegarder le résultat
            $reporter->saveAnalysis($this->rapportId, $result);

            // 3. Mettre à jour le taux global et le HASH sur le modèle Rapport
            // Le statut est déjà mis à jour par le PlagiatReportService, on s'assure juste de mettre les autres champs
            $isPlagiarism = in_array($result['decision'], ['EXACT_MATCH', 'SIMILAR']);
            $rapport->update([
                'taux_plagiat' => $result['taux_global'],
                'hash_document' => $result['hash_document'] ?? null,
                'date_analyse' => now(),
                'statut' => $isPlagiarism ? 'REJETE_PLAGIAT' : 'VALIDE_PLAGIAT'
            ]);

            Log::info("AnalyseRapportPlagiatJob: Analyse terminée pour le rapport $this->rapportId. Taux: " . $result['taux_global'] . "%");

            // Optionnel: Déclencher un événement pour notifier le frontend ou l'étudiant
            // event(new \App\Events\AnalyseCompleted($rapport));

        } catch (\Exception $e) {
            Log::error("AnalyseRapportPlagiatJob: Erreur pour le rapport $this->rapportId - " . $e->getMessage());
            
            // Ne pas mettre à jour le statut avec ERREUR_ANALYSE car non présent dans l'ENUM
            // On le laisse en EN_ATTENTE_ANALYSE_CHEF pour qu'il puisse être relancé
        }
    }
}
