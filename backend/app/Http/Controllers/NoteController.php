<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use App\Models\Rapport;

class NoteController extends Controller
{
    // Pour l'Enseignant : soumettre une note
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->enseignant) {
            return response()->json(['message' => 'Réservé aux enseignants.'], 403);
        }

        $request->validate([
            'rapport_id'  => 'required|exists:rapports,id',
            'note'        => 'required|numeric|min:0|max:20',
            'commentaire' => 'nullable|string',
        ]);

        $rapport = Rapport::findOrFail($request->rapport_id);

        // Vérification que le rapport est bien assigné à cet enseignant
        if ($rapport->enseignant_id !== $user->enseignant->id) {
            return response()->json(['message' => 'Ce rapport ne vous est pas assigné.'], 403);
        }

        $note = Note::updateOrCreate(
            ['rapport_id' => $rapport->id],
            [
                'enseignant_id' => $user->enseignant->id,
                'valeur' => $request->note,
                'commentaire' => $request->commentaire,
                'soumise' => true,
                'soumise_le' => now()
            ]
        );

        // Mettre à jour le statut du rapport
        $rapport->update(['statut' => 'NOTE']);

        return response()->json([
            'message' => 'Note enregistrée avec succès.',
            'note' => $note
        ], 201);
    }

    // Pour l'Étudiant : consulter sa propre note
    public function maNote(Request $request)
    {
        $user = $request->user();
        if (!$user->etudiant) {
            return response()->json(null, 204);
        }

        // On cherche la note du dernier rapport noté
        $note = Note::whereHas('rapport', function($q) use ($user) {
            $q->where('etudiant_id', $user->etudiant->id);
        })
        ->with('rapport')
        ->orderBy('created_at', 'desc')
        ->first();

        if (!$note) {
            return response()->json(null, 204);
        }

        return response()->json([
            'valeur' => $note->valeur,
            'commentaire' => $note->commentaire,
            'date' => $note->soumise_le->format('d/m/Y'),
            'rapport_titre' => $note->rapport->titre
        ]);
    }
}
