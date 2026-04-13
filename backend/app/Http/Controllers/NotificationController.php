<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Filiere;

class NotificationController extends Controller
{
    /**
     * Envoyer une notification (Directeur Adjoint)
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $request->validate([
            'titre' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:themes,rapports,personnalise',
            'cible' => 'required|in:tous,etudiants,enseignants,filiere',
            'filiere_id' => 'required_if:cible,filiere|exists:filieres,id'
        ]);

        $titre = $request->titre;
        $message = $request->message;
        $type = $request->type;
        $cible = $request->cible;
        $filiereId = $request->filiere_id;

        // Déterminer les destinataires
        $destinataires = $this->getDestinataires($cible, $filiereId);

        if (!$destinataires || $destinataires->isEmpty()) {
            return response()->json(['message' => 'Aucun destinataire actif trouvé pour cette cible.'], 400);
        }

        // Créer les notifications
        $notificationsCreated = 0;
        foreach ($destinataires as $user) {
            if (!$user) continue;
            
            Notification::create([
                'user_id' => $user->id,
                'titre' => $titre,
                'message' => $message,
                'type' => $type
            ]);
            $notificationsCreated++;
        }

        if ($notificationsCreated === 0) {
            return response()->json(['message' => 'Aucune notification n\'a pu être générée.'], 400);
        }

        return response()->json([
            'message' => "Notification envoyée à {$notificationsCreated} utilisateur(s) avec succès.",
            'destinataires_count' => $notificationsCreated
        ]);
    }

    /**
     * Récupérer les notifications de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Marquer une notification comme lue
     */
    public function marquerLu(Request $request, $id)
    {
        $user = $request->user();
        
        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->update(['lu' => true]);

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function marquerTousLus(Request $request)
    {
        $user = $request->user();
        
        Notification::where('user_id', $user->id)
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json(['message' => 'Toutes les notifications marquées comme lues.']);
    }

    /**
     * Obtenir les destinataires selon la cible
     */
    private function getDestinataires($cible, $filiereId = null)
    {
        switch ($cible) {
            case 'tous':
                return User::where('role', '!=', 'admin')->get();
                
            case 'etudiants':
                $query = Etudiant::with('user');
                if ($filiereId) {
                    $query->where('filiere_id', $filiereId);
                }
                return $query->get()->pluck('user')->filter();
                
            case 'enseignants':
                return Enseignant::with('user')->get()->pluck('user')->filter();
                
            case 'filiere':
                if ($filiereId) {
                    return Etudiant::where('filiere_id', $filiereId)
                        ->with('user')
                        ->get()
                        ->pluck('user')
                        ->filter();
                }
                break;
                
            default:
                return collect([]);
        }
        
        return collect([]);
    }
}
