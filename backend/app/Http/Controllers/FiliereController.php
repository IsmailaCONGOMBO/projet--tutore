<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use App\Http\Controllers\HistoriqueController;
use Illuminate\Http\Request;

class FiliereController extends Controller
{
    public function index()
    {
        return response()->json(Filiere::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();
        if (empty($data['code'])) {
            $data['code'] = strtoupper(substr($request->nom, 0, 3)) . rand(100, 999);
        }

        $filiere = Filiere::create($data);

        HistoriqueController::log('CREATION_FILIERE', $filiere, "Création de la filière : " . $filiere->nom);

        return response()->json($filiere, 201);
    }

    public function update(Request $request, $id)
    {
        $filiere = Filiere::findOrFail($id);
        
        $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $filiere->update($request->all());

        HistoriqueController::log('UPDATE_FILIERE', $filiere, "Mise à jour de la filière : " . $filiere->nom);

        return response()->json($filiere);
    }

    public function destroy($id)
    {
        $filiere = Filiere::findOrFail($id);
        $nom = $filiere->nom;
        $filiere->delete();

        HistoriqueController::log('SUPPRESSION_FILIERE', null, "Suppression de la filière : " . $nom);

        return response()->json(null, 204);
    }
}
