<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnalysePlagiat;
use App\Models\Rapport;

class AnalyseController extends Controller
{
    /**
     * Récupère les résultats de l'analyse pour un rapport spécifique.
     */
    public function show($rapportId)
    {
        $rapport = Rapport::with('analyse')->findOrFail($rapportId);
        $analyse = $rapport->analyse;

        if (!$analyse) {
            // Fallback si l'analyse a été faite via le nouveau workflow (taux sur le rapport)
            if ($rapport->taux_plagiat !== null) {
                return response()->json([
                    'rapport_id' => $rapport->id,
                    'taux_plagiat' => $rapport->taux_plagiat,
                    'statut' => $rapport->statut,
                    'analyse_date' => $rapport->date_analyse ? $rapport->date_analyse->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                    'passages_suspects' => [
                        [
                            'texte' => "Analyse de conformité effectuée par le département.",
                            'source' => "Archives du département",
                            'similarite' => $rapport->taux_plagiat
                        ]
                    ]
                ]);
            }
            return response()->json(['message' => 'Analyse introuvable ou en cours.'], 404);
        }

        return response()->json([
            'rapport_id' => $analyse->rapport_id,
            'taux_plagiat' => $analyse->taux_plagiat,
            'statut' => $analyse->statut,
            'analyse_date' => $analyse->analyse_le ? $analyse->analyse_le->format('Y-m-d H:i:s') : $analyse->created_at->format('Y-m-d H:i:s'),
            'passages_suspects' => collect($analyse->passages_suspects)->map(function ($p) {
                return [
                    'texte' => $p['texte'] ?? 'N/A',
                    'source' => $p['source_titre'] ?? ($p['source'] ?? 'Source inconnue'),
                    'similarite' => $p['similarite'] ?? 0
                ];
            })
        ]);
    }

    /**
     * Récupère la dernière analyse effectuée pour l'étudiant connecté.
     */
    public function derniere(Request $request)
    {
        $user = $request->user();
        if (!$user->etudiant) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Trouver le dernier rapport de l'étudiant qui a une analyse (soit relation, soit champ direct)
        $rapport = Rapport::where('etudiant_id', $user->etudiant->id)
            ->where(function($q) {
                $q->whereHas('analyse')->orWhereNotNull('taux_plagiat');
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$rapport) {
            return response()->json(['message' => 'Aucune analyse trouvée.'], 404);
        }

        return $this->show($rapport->id);
    }
}
