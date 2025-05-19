<?php

namespace App\Http\Controllers;

use App\Models\PersonnaliteAnalyse;
use App\Models\ScoreTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use App\Models\Candidat;
use App\Models\Offre;

class TestAIController extends Controller
{
    public function generateTest(Request $request)
    {
        // Vérifier si le candidat et l'offre existent
        $candidat = Candidat::find($request->candidat_id);
        $offre = Offre::find($request->offre_id);

        if (!$candidat || !$offre) {
            return response()->json(['error' => 'Candidat ou offre non trouvé'], 404);
        }

        // Vérifier si le candidat a déjà passé le test pour cette offre
        $existingScore = ScoreTest::where('candidat_id', $request->candidat_id)
            ->where('offre_id', $request->offre_id)
            ->first();

        if ($existingScore) {
            // Vérifier le statut du test
            if ($existingScore->status === 'tricher') {
                return response()->json([
                    'error' => 'Test bloqué : triche détectée. Vous n\'êtes pas autorisé à repasser ce test.',
                    'score' => 0,
                    'status' => 'tricher'
                ], 403);
            } else if ($existingScore->status === 'temps ecoule') {
                return response()->json([
                    'error' => 'Test bloqué : temps écoulé. Vous n\'êtes pas autorisé à repasser ce test.',
                    'score' => $existingScore->score_total,
                    'status' => 'temps ecoule'
                ], 403);
            } else if ($existingScore->status === 'terminer') {
                return response()->json([
                    'error' => 'Vous avez déjà passé le test pour cette offre.',
                    'score' => $existingScore->score_total,
                    'status' => 'terminer'
                ], 403);
            }
        }

        // Préparer les données à envoyer à FastAPI
        $offreData = [
            'poste' => $offre->poste,
            'description' => $offre->description,
            'typeTravail' => $offre->typeTravail,
            'niveauExperience' => $offre->niveauExperience,
            'responsabilite' => $offre->responsabilite,
            'experience' => $offre->experience,
        ];

        $poidsData = [
            'ouverture' => $offre->poids_ouverture,
            'conscience' => $offre->poids_conscience,
            'extraversion' => $offre->poids_extraversion,
            'agreabilite' => $offre->poids_agreabilite,
            'stabilite' => $offre->poids_stabilite,
        ];

        try {
            $response = Http::timeout(90)->post('http://127.0.0.1:8003/generate-test', [
                'offre' => $offreData,
                'poids' => $poidsData,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de l\'appel à FastAPI: ' . $e->getMessage()], 500);
        }

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Erreur lors de la génération du test.',
                'details' => $response->body()
            ], $response->status());
        }

        return response()->json($response->json());
    }

    public function storeScore(Request $request)
{
    $validated = $request->validate([
        'candidat_id' => 'required|exists:candidats,id',
        'offre_id' => 'required|exists:offres,id',
        'ouverture' => 'nullable|integer|min:0|max:100',
        'conscience' => 'nullable|integer|min:0|max:100',
        'extraversion' => 'nullable|integer|min:0|max:100',
        'agreabilite' => 'nullable|integer|min:0|max:100',
        'stabilite' => 'nullable|integer|min:0|max:100',
        'questions' => 'nullable|array',
        'answers' => 'nullable|array',
        'status' => 'required|in:terminer,temps ecoule,tricher',
    ]);

    try {
        // Vérifier si un test avec statut "temps ecoule" ou "terminer" existe déjà
        $existingScore = ScoreTest::where('candidat_id', $request->candidat_id)
            ->where('offre_id', $request->offre_id)
            ->whereIn('status', ['temps ecoule', 'terminer'])
            ->first();

        // Si un test complété existe déjà, retourner ce score sans le modifier
        if ($existingScore) {
            return response()->json([
                'message' => 'Score déjà enregistré',
                'score' => $existingScore
            ]);
        }

        $traitScores = $this->calculateTraitPercentages($request->questions, $request->answers);

        $totalScore = 0;
        foreach ($request->answers as $answer) {
            $totalScore += $answer['score'];
        }

        $score = ScoreTest::updateOrCreate(
            [
                'candidat_id' => $request->candidat_id,
                'offre_id' => $request->offre_id,
            ],
            [
                'score_total' => $totalScore,
                'status' => $request->status,
                'ouverture' => $traitScores['ouverture'],
                'conscience' => $traitScores['conscience'],
                'extraversion' => $traitScores['extraversion'],
                'agreabilite' => $traitScores['agreabilite'],
                'stabilite' => $traitScores['stabilite'],
            ]
        );

        if ($request->has('questions') && $request->has('answers')) {
            $this->storeTestResponses($request, $traitScores);
        }

        return response()->json([
            'message' => 'Score enregistré avec succès',
            'score' => $score
        ]);
    } catch (\Exception $e) {
        \Log::error('Erreur ScoreTest: ' . $e->getMessage());
        return response()->json(['error' => 'Erreur lors de l\'enregistrement du score: ' . $e->getMessage()], 500);
    }
}

    /**
     * Calculer les pourcentages pour chaque trait selon la nouvelle formule
     * Pourcentage = (somme des scores obtenus / (nombre de questions * score max par question)) * 100
     */
    private function calculateTraitPercentages($questions, $answers)
    {
        // Initialiser les compteurs pour chaque trait
        $traitCounts = [
            'ouverture' => 0,
            'conscience' => 0,
            'extraversion' => 0,
            'agreabilite' => 0,
            'stabilite' => 0
        ];

        // Initialiser les scores pour chaque trait
        $traitScores = [
            'ouverture' => 0,
            'conscience' => 0,
            'extraversion' => 0,
            'agreabilite' => 0,
            'stabilite' => 0
        ];

        // Tableau de correspondance pour normaliser les noms de traits
        $traitMapping = [
            'ouverture' => 'ouverture',
            'ouverture d\'esprit' => 'ouverture',
            'conscience' => 'conscience',
            'conscienciosité' => 'conscience',
            'extraversion' => 'extraversion',
            'agréabilité' => 'agreabilite',
            'agreabilite' => 'agreabilite',
            'agréabilite' => 'agreabilite',
            'stabilité' => 'stabilite',
            'stabilité émotionnelle' => 'stabilite',
            'stabilite émotionnelle' => 'stabilite',
            'névrosisme' => 'stabilite',
        ];

        // Parcourir les questions pour compter le nombre de questions par trait
        foreach ($questions as $index => $question) {
            $traitOriginal = strtolower($question['trait']);
            $normalizedTrait = isset($traitMapping[$traitOriginal]) ? $traitMapping[$traitOriginal] : $traitOriginal;

            if (isset($traitCounts[$normalizedTrait])) {
                $traitCounts[$normalizedTrait]++;
            }
        }

        // Parcourir les réponses pour calculer les scores par trait
        foreach ($answers as $answer) {
            $questionIndex = $answer['question_index'];
            $question = $questions[$questionIndex];
            $traitOriginal = strtolower($question['trait']);
            $normalizedTrait = isset($traitMapping[$traitOriginal]) ? $traitMapping[$traitOriginal] : $traitOriginal;

            if (isset($traitScores[$normalizedTrait])) {
                $traitScores[$normalizedTrait] += $answer['score'];
            }
        }

        // Calculer les pourcentages selon la formule: (score obtenu / (nombre de questions * 5)) * 100
        $percentages = [];
        foreach ($traitScores as $trait => $score) {
            $questionCount = $traitCounts[$trait];
            $maxPossibleScore = $questionCount * 5; // 5 est le score maximum par question

            // Éviter la division par zéro
            if ($maxPossibleScore > 0) {
                $percentages[$trait] = round(($score / $maxPossibleScore) * 100);
            } else {
                $percentages[$trait] = 0;
            }
        }

        return $percentages;
    }

    /**
     * Stocker les questions et réponses du test dans un fichier JSON
     */
    private function storeTestResponses(Request $request, $traitScores = null)
    {
        try {
            $candidatId = $request->candidat_id;
            $offreId = $request->offre_id;

            // Créer le dossier de stockage s'il n'existe pas
            $storagePath = storage_path('app/tests');
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            // Utiliser les scores calculés s'ils sont fournis, sinon utiliser ceux de la requête
            $scores = $traitScores ?: [
                'total' => $request->score_total,
                'ouverture' => $request->ouverture,
                'conscience' => $request->conscience,
                'extraversion' => $request->extraversion,
                'agreabilite' => $request->agreabilite,
                'stabilite' => $request->stabilite,
            ];

            // Préparer les données à stocker
            $testData = [
                'candidat_id' => $candidatId,
                'offre_id' => $offreId,
                'questions' => $request->questions,
                'answers' => $request->answers,
                'scores' => $scores,
                'completed_at' => now()->toDateTimeString(),
            ];

            // Nom du fichier basé sur candidat_id et offre_id
            $filename = "test_{$candidatId}_{$offreId}.json";

            // Enregistrer le fichier JSON
            file_put_contents("{$storagePath}/{$filename}", json_encode($testData, JSON_PRETTY_PRINT));

            \Log::info("Test responses stored for candidat {$candidatId}, offre {$offreId}");

            return true;
        } catch (\Exception $e) {
            \Log::error("Error storing test responses: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les questions et réponses du test pour un candidat et une offre
     */
    public function getTestResponses($candidatId, $offreId)
    {
        try {
            $storagePath = storage_path('app/tests');
            $filename = "test_{$candidatId}_{$offreId}.json";
            $filePath = "{$storagePath}/{$filename}";

            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Test non trouvé'], 404);
            }

            $testData = json_decode(file_get_contents($filePath), true);

            return response()->json($testData);
        } catch (\Exception $e) {
            \Log::error("Error retrieving test responses: " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la récupération du test'], 500);
        }
    }
}