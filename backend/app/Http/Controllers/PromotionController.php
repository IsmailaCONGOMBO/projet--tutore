<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index()
    {
        return Promotion::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'annee' => 'required|integer',
            'libelle' => 'required|string|max:255',
        ]);

        return Promotion::create($validated);
    }

    public function show(Promotion $promotion)
    {
        return $promotion;
    }

    public function update(Request $request, Promotion $promotion)
    {
        $validated = $request->validate([
            'annee' => 'integer',
            'libelle' => 'string|max:255',
        ]);

        $promotion->update($validated);
        return $promotion;
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();
        return response()->json(null, 204);
    }
}
