<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rapport;
use App\Models\Note;
use App\Models\AnalysePlagiat;
use App\Models\Filiere;
use App\Models\Etudiant;
use DB;

class StatistiqueController extends Controller
{
    /**
     * Récupérer les statistiques globales pour le Directeur Adjoint
     */
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Total des rapports
        $totalRapports = Rapport::count();

        // Taux de plagiat moyen (Priorité au champ direct sur Rapport)
        $tauxPlagiatMoyen = Rapport::whereNotNull('taux_plagiat')
            ->orWhereHas('analyse')
            ->get()
            ->avg(function($r) {
                return $r->taux_plagiat ?? ($r->analyse->taux_global ?? 0);
            }) ?? 0;

        // Statistiques des notes (Flux intégré sur Rapport)
        $notesEnAttente = Rapport::where('statut', 'NOTE_SOUMISE')->count();
        $notesValidees = Rapport::whereIn('statut', ['NOTE_VALIDEE_ADMIN', 'VALIDE_FINAL', 'REJETE_FINAL'])->count();
        $notesRejetees = Rapport::where('statut', 'NOTE_REJETEE_ADMIN')->count();

        // Rapports par filière avec taux de plagiat
        $rapportsParFiliere = DB::table('rapports')
            ->join('etudiants', 'rapports.etudiant_id', '=', 'etudiants.id')
            ->join('filieres', 'etudiants.filiere_id', '=', 'filieres.id')
            ->leftJoin('analyses_plagiat', 'rapports.id', '=', 'analyses_plagiat.rapport_id')
            ->select(
                'filieres.nom as filiere',
                DB::raw('COUNT(rapports.id) as count'),
                DB::raw('AVG(COALESCE(rapports.taux_plagiat, analyses_plagiat.taux_global, 0)) as taux_plagiat')
            )
            ->groupBy('filieres.id', 'filieres.nom')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'total_rapports' => $totalRapports,
            'taux_plagiat_moyen' => round($tauxPlagiatMoyen, 2),
            'notes_en_attente' => $notesEnAttente,
            'notes_validees' => $notesValidees,
            'notes_rejetees' => $notesRejetees,
            'rapports_par_filiere' => $rapportsParFiliere->map(function ($item) {
                return [
                    'filiere' => $item->filiere,
                    'count' => $item->count,
                    'taux_plagiat' => round($item->taux_plagiat, 2)
                ];
            })
        ]);
    }

    /**
     * Évolution mensuelle des rapports et taux de plagiat
     */
    public function evolution(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Données des 6 derniers mois
        $evolution = DB::table('rapports')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mois'),
                DB::raw('COUNT(*) as rapports')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->orderBy('mois')
            ->get();

        // Calculer le taux de plagiat moyen par mois
        $result = [];
        foreach ($evolution as $data) {
            $tauxPlagiat = DB::table('rapports')
                ->leftJoin('analyses_plagiat', 'rapports.id', '=', 'analyses_plagiat.rapport_id')
                ->where(DB::raw('DATE_FORMAT(rapports.created_at, "%Y-%m")'), $data->mois)
                ->avg(DB::raw('COALESCE(rapports.taux_plagiat, analyses_plagiat.taux_global, 0)')) ?? 0;

            $result[] = [
                'mois' => date('M', strtotime($data->mois . '-01')),
                'rapports' => $data->rapports,
                'plagiat' => round($tauxPlagiat, 1)
            ];
        }

        return response()->json($result);
    }
}
