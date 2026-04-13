<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Theme;
use App\Models\Notification;
use App\Models\Etudiant;
use Illuminate\Support\Facades\DB;

class ThemeController extends Controller
{
    /**
     * Pour l'Étudiant : soumettre un nouveau thème
     */
    public function soumettreTheme(Request $request)
    {
        $user = $request->user();
        if (!$user->etudiant) {
            return response()->json(['message' => 'Accès réservé aux étudiants.'], 403);
        }

        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $theme = Theme::create([
            'etudiant_id' => $user->etudiant->id,
            'titre' => $request->titre,
            'description' => $request->description,
            'statut' => 'EN_ATTENTE_CHEF'
        ]);

        return response()->json([
            'message' => 'Thème soumis avec succès.',
            'theme' => $theme
        ], 201);
    }

    /**
     * Pour le Chef : Récupérer les thèmes en attente
     */
    public function getThemesEnAttenteChef(Request $request)
    {
        // On suppose que le rôle 'chef' ou un attribut spécifique identifie le chef
        // Dans ce projet, le chef est souvent l'admin ou a un middleware spécifique
        
        $themes = Theme::with('etudiant.user')
            ->where('statut', 'EN_ATTENTE_CHEF')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($themes);
    }

    /**
     * Pour le Chef : Valider un thème
     */
    public function validerParChef(Request $request, $id)
    {
        $theme = Theme::findOrFail($id);
        
        if ($theme->statut !== 'EN_ATTENTE_CHEF') {
            return response()->json(['message' => 'Statut invalide pour cette action.'], 400);
        }

        // Vérification automatique de similarité avant validation
        $similarite = $this->calculerSimilariteMaximale($theme->titre, $theme->id);
        
        if ($similarite['score'] >= 80) {
            return response()->json([
                'message' => 'Unicité non respectée. Un thème similaire existe déjà.',
                'similaire_a' => $similarite['titre'],
                'score' => $similarite['score']
            ], 422);
        }

        $theme->update([
            'statut' => 'VALIDE_CHEF',
            'valide_par_chef' => $request->user()->id,
            'date_validation_chef' => now()
        ]);

        return response()->json(['message' => 'Thème validé par le Chef et transmis à l\'Admin.']);
    }

    /**
     * Pour le Chef : Rejeter un thème
     */
    public function rejeterParChef(Request $request, $id)
    {
        $request->validate(['motif' => 'required|string']);
        
        $theme = Theme::findOrFail($id);
        $theme->update([
            'statut' => 'REJETE_CHEF',
            'motif_rejet' => $request->motif
        ]);

        // Notification
        Notification::create([
            'user_id' => $theme->etudiant->user_id,
            'titre' => 'Thème rejeté',
            'message' => "Votre thème a été rejeté par le Chef. Motif : " . $request->motif,
            'type' => 'personnalise'
        ]);

        return response()->json(['message' => 'Thème rejeté.']);
    }

    /**
     * Pour l'Admin : Récupérer les thèmes validés par le Chef
     */
    public function getThemesEnAttenteAdmin(Request $request)
    {
        $themes = Theme::with(['etudiant.user', 'chef'])
            ->where('statut', 'VALIDE_CHEF')
            ->orderBy('date_validation_chef', 'desc')
            ->get();

        return response()->json($themes);
    }

    /**
     * Pour l'Admin : Validation finale
     */
    public function validerParAdmin(Request $request, $id)
    {
        $theme = Theme::findOrFail($id);
        $theme->update([
            'statut' => 'VALIDE_ADMIN',
            'valide_par_admin' => $request->user()->id,
            'date_validation_admin' => now()
        ]);

        // Notification de succès final
        Notification::create([
            'user_id' => $theme->etudiant->user_id,
            'titre' => 'Thème validé définitivement',
            'message' => "Félicitations ! Votre thème \"{$theme->titre}\" a été validé par l'Administrateur.",
            'type' => 'rapport'
        ]);

        return response()->json(['message' => 'Thème validé définitivement.']);
    }

    /**
     * Pour l'Admin : Rejeter définitivement
     */
    public function rejeterParAdmin(Request $request, $id)
    {
        $request->validate(['motif' => 'required|string']);
        
        $theme = Theme::findOrFail($id);
        $theme->update([
            'statut' => 'REJETE_ADMIN',
            'motif_rejet' => $request->motif
        ]);

        Notification::create([
            'user_id' => $theme->etudiant->user_id,
            'titre' => 'Thème rejeté par l\'Admin',
            'message' => "Votre thème a été rejeté lors de la validation finale. Motif : " . $request->motif,
            'type' => 'personnalise'
        ]);

        return response()->json(['message' => 'Thème rejeté par l\'Admin.']);
    }

    /**
     * Pour l'Étudiant : Liste de ses thèmes
     */
    public function mesThemes(Request $request)
    {
        $themes = Theme::where('etudiant_id', $request->user()->etudiant->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($themes);
    }

    /**
     * Pour le Chef : Vérifier manuellement la similarité avant validation
     */
    public function checkSimilarity($id)
    {
        $theme = Theme::findOrFail($id);
        $result = $this->calculerSimilariteMaximale($theme->titre, $theme->id);
        return response()->json($result);
    }

    /**
     * Logique de détection de similarité
     */
    private function calculerSimilariteMaximale($titre, $excludeId)
    {
        // Récupérer tous les thèmes validés (Chef ou Admin)
        $themesExistants = Theme::whereIn('statut', ['VALIDE_CHEF', 'VALIDE_ADMIN'])
            ->where('id', '!=', $excludeId)
            ->get();

        $maxScore = 0;
        $titreSimilaire = '';

        foreach ($themesExistants as $existant) {
            similar_text(strtolower($titre), strtolower($existant->titre), $percent);
            if ($percent > $maxScore) {
                $maxScore = $percent;
                $titreSimilaire = $existant->titre;
            }
        }

        return [
            'score' => round($maxScore, 2),
            'titre' => $titreSimilaire
        ];
    }
}
