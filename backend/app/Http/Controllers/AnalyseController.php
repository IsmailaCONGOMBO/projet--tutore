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
        $analyse = AnalysePlagiat::where('rapport_id', $rapportId)->first();

        if (!$analyse) {
            return response()->json(['message' => 'Analyse introuvable ou en cours.'], 404);
        }

        return response()->json([
            'rapport_id' => $analyse->rapport_id,
            'taux_plagiat' => $analyse->taux_plagiat,
            'statut' => $analyse->statut,
            'analyse_date' => $analyse->analyse_le->format('Y-m-d H:i:s'),
            'passages_suspects' => collect($analyse->passages_suspects)->map(function ($p) {
                return [
                    'texte' => $p['texte'],
                    'source' => $p['source_titre'] ?? 'Source inconnue',
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

        // Trouver le dernier rapport de l'étudiant qui a une analyse
        $rapport = Rapport::where('etudiant_id', $user->etudiant->id)
            ->whereHas('analyse')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$rapport) {
            return response()->json(['message' => 'Aucune analyse trouvée.'], 404);
        }

        return $this->show($rapport->id);
    }
}
