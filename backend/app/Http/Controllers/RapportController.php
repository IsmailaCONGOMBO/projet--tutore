<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rapport;
use App\Models\Enseignant;
use App\Models\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class RapportController extends Controller
{
    /**
     * TEST VOLATILE (Étudiant)
     * Aucun enregistrement en base.
     */
    public function testerRapport(Request $request)
    {
        $request->validate(['fichier' => 'required|file|mimes:pdf|max:20480']);
        
        // Utilisation du vrai moteur pour le test (sans sauvegarde en BDD)
        $path = $request->file('fichier')->store('temp', 'local');
        $fullPath = storage_path('app/' . $path);
        
        $analyzer = app(\App\Services\Plagiat\Contracts\PlagiatAnalyzerServiceInterface::class);
        $result = $analyzer->analyze($fullPath, true); // isTest = true
        
        @unlink($fullPath);

        $taux = $result['taux_global'];

        return response()->json([
            'message' => 'Analyse de test terminée.',
            'taux_plagiat' => $taux,
            'statut_previsionnel' => $taux > 20 ? 'REJETE_PLAGIAT' : 'VALIDABLE',
            'seuil' => 20
        ]);
    }

    /**
     * SOUMISSION OFFICIELLE (Étudiant)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->etudiant) {
            return response()->json(['message' => 'Profil étudiant requis.'], 403);
        }

        $request->validate([
            'fichier' => 'required|file|mimes:pdf|max:20480',
            'titre' => 'required|string|max:255',
            'theme_id' => 'nullable|exists:themes,id'
        ]);

        $file = $request->file('fichier');
        $path = $file->store('rapports', 'public');

        $rapport = Rapport::create([
            'etudiant_id' => $user->etudiant->id,
            'theme_id' => $request->theme_id,
            'titre' => $request->titre,
            'fichier_path' => $path,
            'fichier_nom_original' => $file->getClientOriginalName(),
            'fichier_taille' => $file->getSize(),
            'statut' => 'EN_ATTENTE_ANALYSE_CHEF',
            'seuil_plagiat' => 20.0
        ]);

        return response()->json([
            'message' => 'Rapport soumis officiellement. En attente d\'analyse par le Chef.',
            'rapport' => $rapport
        ], 201);
    }

    /**
     * ANALYSE PAR LE CHEF (Chef)
     */
    public function analyserParChef(Request $request, $id)
    {
        $rapport = Rapport::findOrFail($id);

        if ($rapport->statut !== 'EN_ATTENTE_ANALYSE_CHEF') {
            return response()->json(['message' => 'Ce rapport ne peut plus être analysé.'], 400);
        }

        // Utilisation du VRAI job d'analyse de manière synchrone pour que le frontend ait le résultat
        // (En production lourde, on le ferait en asynchrone, mais le frontend attend la réponse immédiate)
        \App\Jobs\AnalyseRapportPlagiatJob::dispatchSync($rapport->id);
        
        // Rafraîchir le rapport pour obtenir les résultats mis à jour
        $rapport->refresh();

        // Notification
        Notification::create([
            'user_id' => $rapport->etudiant->user_id,
            'titre' => 'Résultat d\'analyse de plagiat',
            'message' => $rapport->statut === 'REJETE_PLAGIAT' 
                ? "Votre rapport a été rejeté (Taux: {$rapport->taux_plagiat}%)." 
                : "Votre rapport est recevable (Taux: {$rapport->taux_plagiat}%).",
            'type' => 'rapport'
        ]);

        return response()->json([
            'message' => 'Analyse effectuée avec succès.',
            'taux' => $rapport->taux_plagiat,
            'statut' => $rapport->statut
        ]);
    }

    /**
     * AFFECTATION À UN ENSEIGNANT (Chef)
     */
    public function affecterEnseignant(Request $request, $id)
    {
        $request->validate(['enseignant_id' => 'required|exists:enseignants,id']);
        
        $rapport = Rapport::findOrFail($id);
        if (!in_array($rapport->statut, ['VALIDE_PLAGIAT', 'ASSIGNE_ENSEIGNANT'])) {
            return response()->json(['message' => 'Le rapport doit d\'abord être validé pour plagiat.'], 400);
        }

        $rapport->update([
            'enseignant_id' => $request->enseignant_id,
            'statut' => 'ASSIGNE_ENSEIGNANT'
        ]);

        return response()->json(['message' => 'Enseignant affecté avec succès.']);
    }

    /**
     * RAPPORTS ASSIGNÉS (Enseignant)
     */
    public function assignes(Request $request)
    {
        $user = $request->user();
        if (!$user->enseignant) return response()->json([]);

        $rapports = Rapport::where('enseignant_id', $user->enseignant->id)
            ->whereIn('statut', [
                'ASSIGNE_ENSEIGNANT', 
                'NOTE_SOUMISE', 
                'NOTE_VALIDEE_ADMIN', 
                'NOTE_REJETEE_ADMIN',
                'VALIDE_FINAL',
                'REJETE_FINAL'
            ])
            ->with('etudiant.user')
            ->get();

        return response()->json($rapports);
    }

    /**
     * SOUMISSION NOTE (Enseignant)
     */
    public function soumettreNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|numeric|min:0|max:20',
            'commentaire' => 'required|string'
        ]);

        $rapport = Rapport::findOrFail($id);
        if ($rapport->statut !== 'ASSIGNE_ENSEIGNANT' && $rapport->statut !== 'NOTE_REJETEE_ADMIN') {
            return response()->json(['message' => 'Action impossible à ce stade.'], 400);
        }

        $rapport->update([
            'note' => $request->note,
            'commentaire' => $request->commentaire,
            'statut' => 'NOTE_SOUMISE',
            'date_correction' => now()
        ]);

        return response()->json(['message' => 'Note soumise pour validation Admin.']);
    }

    /**
     * VALIDATION NOTE (Admin)
     */
    public function validerNoteAdmin(Request $request, $id)
    {
        $rapport = Rapport::findOrFail($id);
        $rapport->update([
            'statut' => 'NOTE_VALIDEE_ADMIN',
            'date_validation_admin' => now()
        ]);
        return response()->json(['message' => 'Note validée par l\'Admin.']);
    }

    /**
     * REJET NOTE (Admin)
     */
    public function rejeterNoteAdmin(Request $request, $id)
    {
        $rapport = Rapport::findOrFail($id);
        $rapport->update(['statut' => 'NOTE_REJETEE_ADMIN']);
        return response()->json(['message' => 'Note rejetée. L\'enseignant doit corriger.']);
    }

    /**
     * DÉCISION FINALE (Chef)
     */
    public function decisionFinaleChef(Request $request, $id)
    {
        $request->validate(['decision' => 'required|in:VALIDE_FINAL,REJETE_FINAL']);
        
        $rapport = Rapport::findOrFail($id);
        if ($rapport->statut !== 'NOTE_VALIDEE_ADMIN') {
            return response()->json(['message' => 'Validation Admin requise.'], 400);
        }

        $rapport->update([
            'statut' => $request->decision,
            'date_validation_finale' => now()
        ]);

        Notification::create([
            'user_id' => $rapport->etudiant->user_id,
            'titre' => 'Décision finale du rapport',
            'message' => "La validation finale de votre rapport est : " . str_replace('_', ' ', $request->decision),
            'type' => 'rapport'
        ]);

        return response()->json(['message' => 'Décision finale enregistrée.']);
    }

    /**
     * UTILS
     */
    public function download(Request $request, $id)
    {
        $rapport = Rapport::findOrFail($id);
        return Storage::disk('public')->download($rapport->fichier_path, $rapport->fichier_nom_original);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->etudiant) return response()->json([]);
        return response()->json(Rapport::where('etudiant_id', $user->etudiant->id)->orderBy('created_at', 'desc')->get());
    }

    public function tous(Request $request)
    {
        return response()->json(
            Rapport::with(['etudiant.user', 'enseignant.user'])
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }
}
