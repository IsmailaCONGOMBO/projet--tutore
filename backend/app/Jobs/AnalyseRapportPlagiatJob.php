<?php

namespace App\Jobs;

use App\Models\Rapport;
use App\Models\AnalysePlagiat;
use App\Services\PlagiarismService;
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

    public function __construct($rapportId)
    {
        $this->rapportId = $rapportId;
    }

    public function handle(PlagiarismService $plagiarismService)
    {
        $rapport = Rapport::find($this->rapportId);
        if (!$rapport) return;

        try {
            // Mettre à jour le statut
            $rapport->update(['statut' => 'ANALYSE']);

            // 1. Extraire le texte du nouveau rapport
            $newText = $plagiarismService->extractText($rapport->fichier_path);
            if (empty($newText)) {
                throw new \Exception("Le texte n'a pas pu être extrait du PDF.");
            }

            // 2. Comparer avec les anciens rapports (tous sauf lui-même)
            // On récupère les textes déjà analysés ou archivés
            $autresRapports = Rapport::where('id', '!=', $rapport->id)
                ->whereIn('statut', ['CORRIGE', 'NOTE', 'ARCHIVE'])
                ->get();

            $totalTaux = 0;
            $allSuspectPassages = [];

            foreach ($autresRapports as $autre) {
                // Pour simplifier, on re-extrait le texte. 
                // Dans une vraie app, on stockerait le texte extrait en DB pour gagner du temps.
                $autreText = $plagiarismService->extractText($autre->fichier_path);
                $result = $plagiarismService->compareTexts($newText, $autreText);
                
                if ($result['taux'] > 0) {
                    $totalTaux += $result['taux'];
                    foreach ($result['passages'] as $p) {
                        $p['source_id'] = $autre->id;
                        $p['source_titre'] = $autre->titre;
                        $allSuspectPassages[] = $p;
                    }
                }
            }

            // 3. Enregistrer l'analyse
            AnalysePlagiat::updateOrCreate(
                ['rapport_id' => $rapport->id],
                [
                    'taux_plagiat' => min(100, $totalTaux), // On capte à 100%
                    'passages_suspects' => $allSuspectPassages,
                    'statut' => 'TERMINE',
                    'analyse_le' => now()
                ]
            );

            // 4. Mettre à jour le statut du rapport pour l'enseignant
            $rapport->update(['statut' => 'CORRIGE']); // Prêt pour correction/note

        } catch (\Exception $e) {
            Log::error("Erreur Job Analyse Plagiat : " . $e->getMessage());
            
            AnalysePlagiat::updateOrCreate(
                ['rapport_id' => $rapport->id],
                [
                    'statut' => 'ECHEC',
                    'analyse_le' => now()
                ]
            );
            $rapport->update(['statut' => 'EN_ATTENTE']);
        }
    }
}
