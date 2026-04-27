<?php

namespace App\Http\Controllers;

use App\Models\Historique;
use Illuminate\Http\Request;

class HistoriqueController extends Controller
{
    /**
     * Liste les actions récentes pour l'admin
     */
    public function index(Request $request)
    {
        $query = Historique::with('user')->latest();

        // Filtrer par action si besoin
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Méthode utilitaire pour enregistrer une action
     */
    public static function log($action, $cible = null, $details = null)
    {
        try {
            Historique::create([
                'user_id' => auth()->id() ?? 1, // Fallback à l'admin 1 si pas d'auth (ex: CLI)
                'action' => $action,
                'cible_type' => $cible ? get_class($cible) : null,
                'cible_id' => $cible ? $cible->id : null,
                'details' => is_array($details) ? json_encode($details) : $details,
                'ip_address' => request()->ip()
            ]);
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'enregistrement de l'historique : " . $e->getMessage());
        }
    }
}
