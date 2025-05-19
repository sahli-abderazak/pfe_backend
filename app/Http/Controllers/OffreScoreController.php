<?php

// app/Http/Controllers/OffreScoreController.php

namespace App\Http\Controllers;

use App\Models\OffreScore;
use Illuminate\Http\Request;

class OffreScoreController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'offre_id' => 'required|exists:offres,id',
            'candidat_id' => 'required|exists:candidats,id',
            'score' => 'required|integer|min:1|max:5', // en supposant un score entre 1 et 5
        ]);

        // Vérifier s'il a déjà noté cette offre (optionnel)
        $existing = OffreScore::where('offre_id', $request->offre_id)
            ->where('candidat_id', $request->candidat_id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Score déjà enregistré.'], 400);
        }

        $offreScore = OffreScore::create([
            'offre_id' => $request->offre_id,
            'candidat_id' => $request->candidat_id,
            'score' => $request->score,
        ]);

        return response()->json(['message' => 'Score enregistré avec succès.', 'data' => $offreScore]);
    }
    public function update(Request $request)
    {
        $request->validate([
            'offre_id' => 'required|exists:offres,id',
            'candidat_id' => 'required|exists:candidats,id',
            'score' => 'required|integer|min:1|max:5',
        ]);

        // Trouver le score existant
        $offreScore = OffreScore::where('offre_id', $request->offre_id)
            ->where('candidat_id', $request->candidat_id)
            ->first();

        if (!$offreScore) {
            return response()->json(['message' => 'Score non trouvé.'], 404);
        }

        // Mettre à jour le score
        $offreScore->score = $request->score;
        $offreScore->save();

        return response()->json(['message' => 'Score mis à jour avec succès.', 'data' => $offreScore]);
    }
}