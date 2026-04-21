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
        if ($user->role !== 'etudiant') {
            return response()->json(['message' => 'Accès réservé aux étudiants.'], 403);
        }

        // Lazy creation pour les anciens comptes sans profil
        if (!$user->etudiant) {
            Etudiant::create(['user_id' => $user->id]);
            $user->load('etudiant');
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
     * Pour le Chef : Récupérer l'historique des décisions (thèmes traités)
     */
    public function getHistoriqueChef(Request $request)
    {
        $statut = $request->query('statut');

        // Statuts qui représentent une décision prise
        $statutsDecides = ['VALIDE_CHEF', 'REJETE_CHEF', 'VALIDE_ADMIN', 'REJETE_ADMIN'];

        $query = Theme::with('etudiant.user')
            ->whereIn('statut', $statutsDecides)
            ->orderBy('updated_at', 'desc');

        // Filtrage par catégorie VALIDE / REJETE
        if ($statut === 'VALIDE') {
            $query->whereIn('statut', ['VALIDE_CHEF', 'VALIDE_ADMIN']);
        } elseif ($statut === 'REJETE') {
            $query->whereIn('statut', ['REJETE_CHEF', 'REJETE_ADMIN']);
        }

        $themes = $query->get()->map(function ($theme) {
            return [
                'id'          => $theme->id,
                'titre'       => $theme->titre,
                'description' => $theme->description,
                'statut'      => $this->normaliserStatut($theme->statut),
                'statut_raw'  => $theme->statut,
                'motif_rejet' => $theme->motif_rejet,
                'created_at'  => $theme->created_at,
                'updated_at'  => $theme->updated_at,
                'etudiant'    => $theme->etudiant ? [
                    'id'    => $theme->etudiant->id,
                    'name'  => $theme->etudiant->user->name ?? 'Inconnu',
                    'email' => $theme->etudiant->user->email ?? '',
                ] : null,
            ];
        });

        return response()->json($themes);
    }

    /**
     * Pour le Chef : Rechercher des thèmes par mot-clé
     */
    public function rechercherThemes(Request $request)
    {
        $motCle = $request->query('motCle', '');

        $themes = Theme::with('etudiant.user')
            ->where(function ($q) use ($motCle) {
                $q->where('titre', 'like', "%{$motCle}%")
                  ->orWhere('description', 'like', "%{$motCle}%");
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($themes);
    }

    /**
     * Normalise les statuts DB en statuts simplifiés pour le frontend
     */
    private function normaliserStatut(string $statut): string
    {
        if (in_array($statut, ['VALIDE_CHEF', 'VALIDE_ADMIN'])) return 'VALIDE';
        if (in_array($statut, ['REJETE_CHEF', 'REJETE_ADMIN'])) return 'REJETE';
        return 'EN_ATTENTE';
    }

    /**
     * Pour le Chef : Récupérer les thèmes en attente
     */
    public function getThemesEnAttenteChef(Request $request)
    {
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
        $user = $request->user();
        
        if ($user->role !== 'etudiant') {
            return response()->json(['message' => 'Accès réservé aux étudiants.'], 403);
        }

        if (!$user->etudiant) {
            Etudiant::create(['user_id' => $user->id]);
            $user->load('etudiant');
        }

        $themes = Theme::where('etudiant_id', $user->etudiant->id)
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
