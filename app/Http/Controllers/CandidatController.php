<?php

namespace App\Http\Controllers;

use App\Models\Candidat;
use App\Models\Interview;
use App\Models\MatchingScore;
use App\Models\Notification;
use App\Models\Offre;
use App\Models\OffreScore;
use App\Models\ScoreTest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

class CandidatController extends Controller
{
    public function getCandidatByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'offre_id' => 'required|exists:offres,id',
        ]);

        $candidat = Candidat::where('email', $request->email)
                           ->where('offre_id', $request->offre_id)
                           ->first();

        if (!$candidat) {
            return response()->json(['error' => 'Candidat non trouvé'], 404);
        }

        return response()->json($candidat);
    }
    /**
 * @OA\Post(
 *     path="/api/candidatStore",
 *     summary="Ajouter un candidat",
 *     operationId="storeCandidat",
 *     tags={"Candidat"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Ajouter un candidat avec ses informations",
 *         @OA\JsonContent(
 *             required={"nom", "prenom", "email", "pays", "ville", "codePostal", "tel", "niveauEtude", "cv", "offre_id"},
 *             @OA\Property(property="nom", type="string", example="Doe"),
 *             @OA\Property(property="prenom", type="string", example="John"),
 *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
 *             @OA\Property(property="pays", type="string", example="France"),
 *             @OA\Property(property="ville", type="string", example="Paris"),
 *             @OA\Property(property="codePostal", type="string", example="75001"),
 *             @OA\Property(property="tel", type="string", example="0102030405"),
 *             @OA\Property(property="niveauEtude", type="string", example="Bac+5"),
 *             @OA\Property(property="niveauExperience", type="string", example="3 ans"),
 *             @OA\Property(property="cv", type="string", format="binary"),
 *             @OA\Property(property="offre_id", type="integer", example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Candidat ajouté avec succès.",
 *         @OA\JsonContent(
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="nom", type="string", example="Doe"),
 *             @OA\Property(property="prenom", type="string", example="John")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Vous avez déjà postulé à cette offre.",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Vous avez déjà postulé à cette offre.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation des données échouée.",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Les données envoyées sont invalides.")
 *         )
 *     )
 * )
 */
public function storeCandidat(Request $request)
{
    // Validation des données reçues
    $request->validate([
        'nom' => 'required|string|max:255',
        'prenom' => 'required|string|max:255',
        'email' => 'required|email',
        'pays' => 'required|string|max:255',
        'ville' => 'required|string|max:255',
        'codePostal' => 'required|string|max:10',
        'tel' => 'required|string|max:20',
        'niveauEtude' => 'required|string|max:255',
        'niveauExperience' => 'nullable|string|max:255',
        'cv' => 'required|file|mimes:pdf,doc,docx|max:2048',
        'offre_id' => 'required|exists:offres,id',
    ]);

    // Vérifier si le candidat a déjà postulé à cette offre avec le même email
    $existingApplication = Candidat::where('email', $request->email)
        ->where('offre_id', $request->offre_id)
        ->first();

    if ($existingApplication) {
        return response()->json([
            'error' => 'Vous avez déjà postulé à cette offre. Vous ne pouvez postuler qu\'une seule fois par offre.'
        ], 400);
    }

    // Sauvegarde du CV
    if ($request->hasFile('cv')) {
        $cvPath = $request->file('cv')->store('cvs', 'public'); // Sauvegarde dans storage/app/public/cvs
    }

    // Lire le contenu du CV
    $cvText = (new PdfParser())
        ->parseFile(public_path("storage/" . $cvPath))
        ->getText();

    // Récupérer l'offre d'emploi
    $offre = Offre::find($request->offre_id);

    // Appel à l'API FastAPI pour obtenir le score de matching entre le CV et l'offre
    $response = Http::post('http://127.0.0.1:8003/match-cv-offre', [
        'cv' => $cvText,
        'offre' => [
            'id' => $offre->id,
            'poste' => $offre->poste,
            'description' => $offre->description,
            'niveauExperience' => $offre->niveauExperience,
            'niveauEtude' => $offre->niveauEtude,
            'responsabilite' => $offre->responsabilite,
            'experience' => $offre->experience,
            'pays' => $offre->pays,
            'ville' => $offre->ville,
        ]
    ]);

    // Vérifier la réponse de l'API
    if (!$response->ok()) {
        return response()->json([
            'error' => 'Erreur lors de l\'appel à l\'API FastAPI pour le matching.',
            'details' => $response->body(),
        ], 500);
    }

    // Récupérer le score de matching depuis la réponse de l'API
    $data = $response->json();
    $score = $data['score'] ?? 0;
    $scoreMinimal = $offre->matching ?? 0;

    // Vérifier si le score est suffisant pour ajouter le candidat
    if ($score < $scoreMinimal) {
        return response()->json([
            'error' => "Votre score de matching ($score) est inférieur au minimum requis ($scoreMinimal). Nous ne pouvons pas retenir votre candidature pour ce poste.",
        ], 403);
    }

    // Créer le candidat dans la table Candidat
    $candidat = Candidat::create([
        'nom' => $request->nom,
        'prenom' => $request->prenom,
        'email' => $request->email,
        'pays' => $request->pays,
        'ville' => $request->ville,
        'codePostal' => $request->codePostal,
        'tel' => $request->tel,
        'niveauEtude' => $request->niveauEtude,
        'niveauExperience' => $request->niveauExperience,
        'cv' => $cvPath ?? null,
        'offre_id' => $request->offre_id,
    ]);

    // Trouver les recruteurs de la même société pour notification
    if ($offre) {
        $recruiters = User::where('nom_societe', $offre->societe)
            ->where('role', 'recruteur')
            ->where('active', true)
            ->get();

        // Créer une notification pour chaque recruteur
        foreach ($recruiters as $recruiter) {
            Notification::create([
                'type' => 'new_application',
                'message' => "Un candidat a postulé à votre offre d'emploi '{$offre->poste}'",
                'data' => [
                    'candidate_id' => $candidat->id,
                    'candidate_name' => $candidat->nom . ' ' . $candidat->prenom,
                    'candidate_email' => $candidat->email,
                    'offer_id' => $offre->id,
                    'position' => $offre->poste,
                    'department' => $offre->departement,
                    'company' => $offre->societe,
                    'application_id' => $candidat->id, // Pour navigation dans le frontend
                ],
                'user_id' => $recruiter->id,
                'read' => false,
            ]);
        }
    }

    // Retourner la réponse avec le candidat créé
    return response()->json($candidat, 201);
}
    
    /**
 * @OA\Get(
 *     path="/api/candidats-offre",
 *     summary="Afficher les candidats d'une offre",
 *     operationId="showcandidatOffre",
 *     tags={"Candidat"},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des candidats",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="nom", type="string", example="Doe"),
 *                 @OA\Property(property="prenom", type="string", example="John"),
 *                 @OA\Property(property="cv", type="string", example="https://example.com/cvs/cv.pdf")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Utilisateur non authentifié.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Utilisateur non authentifié")
 *         )
 *     )
 * )
 */

public function showcandidatOffre()
{
    $user = Auth::user(); // Récupérer l'utilisateur connecté

    if (!$user) {
        return response()->json(['message' => 'Utilisateur non authentifié'], 401);
    }

    // Filtrer les candidats non archivés, dont l'offre appartient à la même société,
    // et qui n'ont pas un ScoreTest avec status = 'tricher'
    $candidats = Candidat::where('archived', 0)
        ->whereHas('offre', function ($query) use ($user) {
            $query->where('societe', $user->nom_societe);
        })
        ->whereDoesntHave('scoreTest', function ($query) {
            $query->where('status', 'tricher');
        })
        ->with(['offre:id,departement,domaine,datePublication,poste'])
        ->get();

    // Ajouter le chemin du CV pour chaque candidat
    foreach ($candidats as $candidat) {
        $candidat->cv = $candidat->cv ? asset('storage/' . $candidat->cv) : null;
    }

    return response()->json($candidats);
}

    // public function showcandidatOffre()
    // {
    //     $user = Auth::user(); // Récupérer l'utilisateur connecté
    
    //     if (!$user) {
    //         return response()->json(['message' => 'Utilisateur non authentifié'], 401);
    //     }
    
    //     // Filtrer les candidats non archivés dont l'offre appartient à la même société que l'utilisateur connecté
    //     $candidats = Candidat::where('archived', 0)
    //         ->whereHas('offre', function ($query) use ($user) {
    //             $query->where('societe', $user->nom_societe);
    //         })
    //         ->with(['offre:id,departement,domaine,datePublication,poste'])
    //         ->get();
    
    //     // Ajouter le chemin du CV pour chaque candidat
    //     foreach ($candidats as $candidat) {
    //         $candidat->cv = $candidat->cv ? asset('storage/' . $candidat->cv) : null;
    //     }
    
    //     return response()->json($candidats);
    // }

        /**
 * @OA\Put(
 *     path="/api/candidats/archiver/{id}",
 *     summary="Archiver un candidat",
 *     operationId="archiverCandidat",
 *     tags={"Candidat"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID du candidat",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Candidat archivé avec succès.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Candidat archivé avec succès"),
 *             @OA\Property(property="candidat", type="object", ref="#/components/schemas/Candidat")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Candidat non trouvé.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Candidat non trouvé")
 *         )
 *     )
 * )
 */
    public function archiverCandidat($id)
    {
        // Récupérer le candidat par son ID
        $candidat = Candidat::find($id);
    
        if (!$candidat) {
            return response()->json(['message' => 'Candidat non trouvé'], 404);
        }
    
        // Mettre à jour le champ "archived"
        $candidat->archived = true;
        $candidat->save();
    
        return response()->json(['message' => 'Candidat archivé avec succès', 'candidat' => $candidat], 200);
    }
/**
 * @OA\Get(
 *     path="/api/candidats_archived_societe",
 *     summary="Récupérer les candidats archivés d'une société",
 *     operationId="getArchivedCandidatesByCompany",
 *     tags={"Candidat"},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des candidats archivés",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="nom", type="string", example="Doe"),
 *                 @OA\Property(property="prenom", type="string", example="John")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Utilisateur non authentifié.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Utilisateur non authentifié")
 *         )
 *     )
 * )
 */
    public function getArchivedCandidatesByCompany(Request $request)
{
    $user = Auth::user(); // Récupérer l'utilisateur connecté

    if (!$user) {
        return response()->json(['message' => 'Utilisateur non authentifié'], 401);
    }

    // Filtrer les candidats non archivés dont l'offre appartient à la même société que l'utilisateur connecté
    $candidats = Candidat::where('archived', 1)
        ->whereHas('offre', function ($query) use ($user) {
            $query->where('societe', $user->nom_societe);
        })
        ->with(['offre:id,departement,domaine,datePublication,poste'])
        ->get();

    // Ajouter le chemin du CV pour chaque candidat
    foreach ($candidats as $candidat) {
        $candidat->cv = $candidat->cv ? asset('storage/' . $candidat->cv) : null;
    }

    return response()->json($candidats);
}
/**
 * @OA\Put(
 *     path="/api/candidats_desarchiver/{id}",
 *     summary="Désarchiver un candidat",
 *     operationId="desarchiverCandidat",
 *     tags={"Candidat"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID du candidat",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Candidat désarchivé avec succès.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Candidat désarchivé avec succès"),
 *             @OA\Property(property="candidat", type="object", ref="#/components/schemas/Candidat")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Candidat non trouvé.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Candidat non trouvé")
 *         )
 *     )
 * )
 */
public function desarchiverCandidat($id)
    {
        // Récupérer le candidat par son ID
        $candidat = Candidat::find($id);
    
        if (!$candidat) {
            return response()->json(['message' => 'Candidat non trouvé'], 404);
        }
    
        // Mettre à jour le champ "archived"
        $candidat->archived = false;
        $candidat->save();
    
        return response()->json(['message' => 'Candidat archivé avec succès', 'candidat' => $candidat], 200);
    }
/**
 * @OA\Get(
 *     path="/api/recherche-candidat",
 *     summary="Rechercher un candidat",
 *     operationId="rechercheCandidat",
 *     tags={"Candidat"},
 *     @OA\Parameter(
 *         name="nom",
 *         in="query",
 *         description="Nom du candidat",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="prenom",
 *         in="query",
 *         description="Prénom du candidat",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des candidats trouvés",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 @OA\Property(property="nom", type="string", example="Doe"),
 *                 @OA\Property(property="prenom", type="string", example="John")
 *             )
 *         )
 *     )
 * )
 */
    public function rechercheCandidat($nom = null, $prenom = null)
{
    $query = Candidat::query();

    if (!empty($nom)) {
        $query->where('nom', 'like', '%' . $nom . '%');
    }
    if (!empty($prenom)) {
        $query->where('prenom', 'like', '%' . $prenom . '%');
    }

    return $query->get();
}
/**
 * @OA\Delete(
 *     path="/api/candidatSupp/{id}",
 *     summary="Supprimer un candidat",
 *     operationId="deleteCandidat",
 *     tags={"Candidat"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID du candidat",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Candidat supprimé avec succès.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Candidat supprimé avec succès")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Candidat non trouvé.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Candidat non trouvé")
 *         )
 *     )
 * )
 */
public function deleteCandidat($id)
{
    $candidat = Candidat::find($id);

    if (!$candidat) {
        return response()->json(['message' => 'Candidat non trouvé'], 404);
    }

    // Supprimer les fichiers (CV)
    if ($candidat->cv) {
        Storage::disk('public')->delete($candidat->cv);
    }

    // Supprimer les entités liées
    Interview::where('candidat_id', $id)->delete();
    MatchingScore::where('candidat_id', $id)->delete();
    OffreScore::where('candidat_id', $id)->delete();
    ScoreTest::where('candidat_id', $id)->delete();

    // Supprimer le candidat
    $candidat->delete();

    return response()->json(['message' => 'Candidat supprimé avec succès'], 200);
}
/**
 * @OA\Get(
 *     path="/api/candidatsByOffre/{offre_id}",
 *     summary="Obtenir les candidats par offre",
 *     operationId="getCandidatsByOffre",
 *     tags={"Candidat"},
 *     @OA\Parameter(
 *         name="offre_id",
 *         in="path",
 *         required=true,
 *         description="ID de l'offre",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des candidats pour l'offre",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="nom", type="string", example="Doe"),
 *                 @OA\Property(property="prenom", type="string", example="John")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Offre non trouvée.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Offre non trouvée")
 *         )
 *     )
 * )
 */
public function getCandidatsByOffre($offre_id)
{
    // Vérifier si l'offre existe
    $offre = Offre::find($offre_id);
    
    if (!$offre) {
        return response()->json(['message' => 'Offre non trouvée'], 404);
    }

    // Récupérer les candidats liés à cette offre
    $candidats = Candidat::where('offre_id', $offre_id)
        ->with('offre:id,poste,departement') // Charger les détails de l'offre
        ->get();

    // Ajouter l'URL du CV pour chaque candidat
    foreach ($candidats as $candidat) {
        $candidat->cv = $candidat->cv ? asset('storage/' . $candidat->cv) : null;
    }

    return response()->json($candidats, 200);
}
public function getCandidatsByOffreStatus($offre_id)
{
    // Vérifier si l'offre existe
    $offre = Offre::find($offre_id);
    
    if (!$offre) {
        return response()->json(['message' => 'Offre non trouvée'], 404);
    }

    // Récupérer les candidats liés à cette offre ET ayant un test avec statut "terminer" ou "temps ecoule"
    $candidats = Candidat::where('offre_id', $offre_id)
        ->whereHas('scoreTest', function ($query) {
            $query->whereIn('status', ['terminer', 'temps ecoule']);
        })
        ->with([
            'offre:id,poste,departement',
            'scoreTest' => function ($query) {
                $query->select('id', 'candidat_id', 'status', 'score_total'); // facultatif
            }
        ])
        ->get();

    // Ajouter l'URL du CV et le status du test pour chaque candidat
    foreach ($candidats as $candidat) {
        $candidat->cv = $candidat->cv ? asset('storage/' . $candidat->cv) : null;
        $candidat->test_status = $candidat->scoreTest->status ?? null; // ajoute "terminer" ou "temps ecoule"
    }

    return response()->json($candidats, 200);
}

/**
 * @OA\Get(
 *     path="/api/candidats/offres/{email}",
 *     summary="Afficher les offres d'un candidat",
 *     operationId="offresParCandidat",
 *     tags={"Candidat"},
 *     @OA\Parameter(
 *         name="email",
 *         in="path",
 *         required=true,
 *         description="Email du candidat",
 *         @OA\Schema(type="string", format="email", example="johndoe@example.com")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres auxquelles le candidat a postulé",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 @OA\Property(property="nom", type="string", example="Doe"),
 *                 @OA\Property(property="prenom", type="string", example="John")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Candidat non trouvé.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Candidat non trouvé")
 *         )
 *     )
 * )
 */
public function offresParCandidat($email)
{
    // Vérifier si l'utilisateur est authentifié
    $recruteur = Auth::user();

    if (!$recruteur || $recruteur->role !== 'recruteur') {
        return response()->json(['message' => 'Accès refusé'], 403);
    }

    // Récupérer les offres postulées par le candidat qui appartiennent à la société du recruteur connecté
    $candidat = DB::table('candidats')
        ->where('candidats.email', $email)
        ->join('offres', 'candidats.offre_id', '=', 'offres.id')
        ->where('offres.societe', '=', $recruteur->nom_societe) // Filtrer par la société du recruteur
        ->select('candidats.nom', 'candidats.prenom', 'candidats.email', 'offres.*')
        ->get();

    if ($candidat->isEmpty()) {
        return response()->json(['message' => 'Aucune offre trouvée pour ce candidat dans votre société'], 404);
    }

    return response()->json($candidat);
}

}