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
     * Statistiques par filière
     */
    public function filiere(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $stats = DB::table('filieres')
            ->leftJoin('etudiants', 'filieres.id', '=', 'etudiants.filiere_id')
            ->leftJoin('rapports', 'etudiants.id', '=', 'rapports.etudiant_id')
            ->select(
                'filieres.id',
                'filieres.nom',
                DB::raw('COUNT(rapports.id) as total_rapports'),
                DB::raw('AVG(COALESCE(rapports.taux_plagiat, 0)) as taux_plagiat_moyen')
            )
            ->groupBy('filieres.id', 'filieres.nom')
            ->get();

        return response()->json($stats);
    }

    /**
     * Statistiques par promotion
     */
    public function promotion(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $stats = DB::table('promotions')
            ->leftJoin('etudiants', 'promotions.id', '=', 'etudiants.promotion_id')
            ->leftJoin('rapports', 'etudiants.id', '=', 'rapports.etudiant_id')
            ->select(
                'promotions.id',
                'promotions.annee',
                'promotions.libelle',
                DB::raw('COUNT(rapports.id) as total_rapports'),
                DB::raw('AVG(COALESCE(rapports.taux_plagiat, 0)) as taux_plagiat_moyen')
            )
            ->groupBy('promotions.id', 'promotions.annee', 'promotions.libelle')
            ->orderBy('promotions.annee', 'desc')
            ->get();

        return response()->json($stats);
    }

    /**
     * Statistiques globales et meilleure filière
     */
    public function global(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $totalRapports = Rapport::count();
        $totalEtudiants = Etudiant::count();
        
        $tauxMoyen = Rapport::avg('taux_plagiat') ?? 0;

        // Meilleure filière (taux de plagiat le plus bas, avec au moins un rapport)
        $meilleureFiliere = DB::table('filieres')
            ->join('etudiants', 'filieres.id', '=', 'etudiants.filiere_id')
            ->join('rapports', 'etudiants.id', '=', 'rapports.etudiant_id')
            ->select(
                'filieres.nom',
                DB::raw('AVG(rapports.taux_plagiat) as taux_moyen')
            )
            ->groupBy('filieres.id', 'filieres.nom')
            ->orderBy('taux_moyen', 'asc')
            ->first();

        // Rapports validés/rejetés
        $rapportsValides = Rapport::where('statut', 'VALIDE_FINAL')->count();
        $rapportsRejetes = Rapport::where('statut', 'REJETE_FINAL')->count();

        return response()->json([
            'total_rapports' => $totalRapports,
            'total_etudiants' => $totalEtudiants,
            'taux_plagiat_moyen' => round($tauxMoyen, 2),
            'meilleure_filiere' => $meilleureFiliere,
            'rapports_valides' => $rapportsValides,
            'rapports_rejetes' => $rapportsRejetes
        ]);
    }
}
