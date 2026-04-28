<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Etudiant;
use App\Models\Enseignant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Charger les profils avec les relations pour voir les filières et promotions
        $query->with(['etudiant.filiere', 'etudiant.promotion', 'enseignant.filiere']);

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'role'         => ['required', Rule::in(['admin', 'etudiant', 'enseignant', 'chef_departement'])],
            'password'     => 'required|string|min:4',
            'filiere_id'   => 'nullable|integer|exists:filieres,id',
            'promotion_id' => 'nullable|integer|exists:promotions,id',
        ]);

        $data['password'] = bcrypt($data['password']);
        $user = User::create($data);

        // Création automatique du profil selon le rôle avec filiere_id/promotion_id
        if ($user->role === 'etudiant') {
            Etudiant::create([
                'user_id'      => $user->id,
                'filiere_id'   => $request->filiere_id,
                'promotion_id' => $request->promotion_id
            ]);
        } elseif (in_array($user->role, ['enseignant', 'chef_departement'])) {
            Enseignant::create([
                'user_id'    => $user->id,
                'filiere_id' => $request->filiere_id
            ]);
        }

        return response()->json(
            $user->load(['etudiant.filiere', 'etudiant.promotion', 'enseignant.filiere']),
            201
        );
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'email'        => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'role'         => ['sometimes', Rule::in(['admin', 'etudiant', 'enseignant', 'chef_departement'])],
            'password'     => 'sometimes|string|min:4',
            'filiere_id'   => 'nullable|integer|exists:filieres,id',
            'promotion_id' => 'nullable|integer|exists:promotions,id',
        ]);

        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);

        // Mise à jour du profil
        if ($user->role === 'etudiant') {
            $user->etudiant()->updateOrCreate(
                ['user_id' => $user->id], 
                ['filiere_id' => $request->filiere_id, 'promotion_id' => $request->promotion_id]
            );
        } elseif (in_array($user->role, ['enseignant', 'chef_departement'])) {
            $user->enseignant()->updateOrCreate(['user_id' => $user->id], ['filiere_id' => $request->filiere_id]);
        }

        return response()->json($user->load(['etudiant.filiere', 'etudiant.promotion', 'enseignant.filiere']));
    }

    public function destroy(User $user)
    {
        if ($user->role === 'admin' && User::where('role', 'admin')->count() === 1) {
            return response()->json(['message' => 'Impossible de supprimer le seul administrateur.'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé.']);
    }
}
