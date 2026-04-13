<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use App\Models\Rapport;
use App\Models\Notification;

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
            'rapport_titre' => $note->rapport->titre,
            'statut_validation' => $note->statut_validation,
            'motif_rejet' => $note->motif_rejet
        ]);
    }

    // Pour l'Admin : récupérer toutes les notes en attente
    public function enAttente(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $notes = Note::with(['rapport.etudiant.user', 'enseignant.user'])
            ->where('statut_validation', 'EN ATTENTE')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notes);
    }

    // Pour l'Admin : valider une note
    public function valider(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $note = Note::with('rapport.etudiant.user')->findOrFail($id);
        $note->update([
            'statut_validation' => 'VALIDÉ',
            'motif_rejet' => null
        ]);

        // Notifier l'étudiant
        if ($note->rapport && $note->rapport->etudiant && $note->rapport->etudiant->user) {
            Notification::create([
                'user_id' => $note->rapport->etudiant->user->id,
                'titre' => 'Note validée',
                'message' => 'Votre note pour le rapport "' . $note->rapport->titre . '" a été validée.',
                'type' => 'rapport'
            ]);
        }

        return response()->json(['message' => 'Note validée.']);
    }

    // Pour l'Admin : rejeter une note
    public function rejeter(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $request->validate(['motif' => 'required|string']);

        $note = Note::with(['enseignant.user', 'rapport'])->findOrFail($id);
        $note->update([
            'statut_validation' => 'REJETÉ',
            'motif_rejet' => $request->motif
        ]);

        // Notifier l'enseignant
        if ($note->enseignant && $note->enseignant->user) {
            Notification::create([
                'user_id' => $note->enseignant->user->id,
                'titre' => 'Note rejetée',
                'message' => 'La note soumise pour le rapport "' . $note->rapport->titre . '" a été rejetée. Motif : ' . $request->motif,
                'type' => 'personnalise'
            ]);
        }

        return response()->json(['message' => 'Note rejetée.']);
    }
}
