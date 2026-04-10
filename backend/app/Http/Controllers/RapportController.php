<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rapport;
use App\Models\Enseignant;
use Illuminate\Support\Facades\Storage;

class RapportController extends Controller
{
    // Pour l'Enseignant : rapports assignés à lui par le chef de département
    public function assignes(Request $request)
    {
        $user = $request->user();
        if (!$user->enseignant) {
            return response()->json([], 200); // Pas un enseignant
        }

        $rapports = Rapport::where('enseignant_id', $user->enseignant->id)
            ->where('archive', false)
            ->with(['etudiant.user', 'analyse']) // Ajout de 'analyse'
            ->orderBy('created_at', 'desc')
            ->get();

        // Formater les données pour le frontend
        $result = $rapports->map(function ($r) {
            return [
                'id' => $r->id,
                'titre' => $r->titre,
                'etudiant' => $r->etudiant->user->name,
                'date_depot' => $r->created_at->format('Y-m-d'),
                'statut' => $r->statut,
                'taux_plagiat' => $r->analyse ? $r->analyse->taux_plagiat : null,
                'file_url' => asset('storage/' . $r->fichier_path)
            ];
        });

        return response()->json($result);
    }

    // Pour l'Enseignant : rapports archivés
    public function archives(Request $request)
    {
        $user = $request->user();
        if (!$user->enseignant) {
            return response()->json([], 200);
        }

        $rapports = Rapport::where('enseignant_id', $user->enseignant->id)
            ->where('archive', true)
            ->with('etudiant.user')
            ->orderBy('created_at', 'desc')
            ->get();

        $result = $rapports->map(function ($r) {
            return [
                'id' => $r->id,
                'titre' => $r->titre,
                'etudiant' => $r->etudiant->user->name,
                'date_depot' => $r->created_at->format('Y-m-d'),
                'statut' => $r->statut,
                'file_url' => asset('storage/' . $r->fichier_path)
            ];
        });

        return response()->json($result);
    }

    // Pour l'Enseignant : télécharger un rapport PDF
    public function download(Request $request, $id)
    {
        // On vérifie que l'enseignant a bien le droit sur ce rapport
        $user = $request->user();
        $rapport = Rapport::findOrFail($id);

        if ($user->enseignant && $rapport->enseignant_id !== $user->enseignant->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        if (!Storage::disk('public')->exists($rapport->fichier_path)) {
            return response()->json(['message' => 'Fichier introuvable sur le serveur.'], 404);
        }

        return Storage::disk('public')->download($rapport->fichier_path, $rapport->fichier_nom_original);
    }

    // Pour l'Étudiant : consulter ses propres rapports
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->etudiant) {
            return response()->json([], 200);
        }

        $rapports = Rapport::where('etudiant_id', $user->etudiant->id)
            ->with('analyse')
            ->orderBy('created_at', 'desc')
            ->get();

        $result = $rapports->map(function ($r) {
            return [
                'id' => $r->id,
                'titre' => $r->titre,
                'statut' => $r->statut,
                'taux_plagiat' => $r->analyse ? $r->analyse->taux_plagiat : null,
                'created_at' => $r->created_at->format('Y-m-d H:i:s'),
                'file_url' => asset('storage/' . $r->fichier_path)
            ];
        });

        return response()->json($result);
    }

    // Pour l'Étudiant : déposer un rapport PDF
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->etudiant) {
            return response()->json(['message' => 'Accès refusé. Profil étudiant introuvable.'], 403);
        }

        $request->validate([
            'fichier' => 'required|file|mimes:pdf|max:20480', // Max 20Mo
            'titre' => 'nullable|string|max:255'
        ]);

        $file = $request->file('fichier');
        $originalName = $file->getClientOriginalName();
        $size = $file->getSize();

        // Sauvegarde dans storage/app/public/rapports
        $path = $file->store('rapports', 'public');

        // MOCK TEMPORAIRE : assigner au premier enseignant trouvé (s'il y en a un)
        $enseignant = Enseignant::first();

        $rapport = Rapport::create([
            'etudiant_id' => $user->etudiant->id,
            'enseignant_id' => $enseignant ? $enseignant->id : null,
            'titre' => $request->titre ?: 'Rapport de ' . $user->name,
            'fichier_path' => $path,
            'fichier_nom_original' => $originalName,
            'fichier_taille' => $size,
            'statut' => 'EN_ATTENTE',
            'archive' => false
        ]);

        // Déclencher l'analyse de plagiat en arrière-plan
        \App\Jobs\AnalyseRapportPlagiatJob::dispatch($rapport->id);

        return response()->json([
            'message' => 'Rapport déposé avec succès. L\'analyse est en cours.',
            'rapport' => $rapport
        ], 201);
    }
}
