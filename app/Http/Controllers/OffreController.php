<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Carbon\Carbon;

use Illuminate\Http\Request;
use App\Models\Offre;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;
class OffreController extends Controller
{
/**
 * @OA\Post(
 *     path="/api/addOffres",
 *     tags={"Offre"},
 *     summary="Ajouter une offre",
 *     description="Ajoute une nouvelle offre à la base de données et notifie les administrateurs.",
 *     security={{"sanctum":{}}},
 *     requestBody={
 *         @OA\RequestBody(
 *             required=true,
 *             @OA\JsonContent(
 *                 required={"departement", "poste", "description", "dateExpiration", "typePoste", "typeTravail", "heureTravail", "niveauExperience", "niveauEtude", "pays", "ville", "societe", "domaine", "responsabilite", "experience"},
 *                 @OA\Property(property="departement", type="string", example="Informatique"),
 *                 @OA\Property(property="poste", type="string", example="Développeur Web"),
 *                 @OA\Property(property="description", type="string", example="Développeur Web pour projet entreprise."),
 *                 @OA\Property(property="dateExpiration", type="string", format="date", example="2025-06-30"),
 *                 @OA\Property(property="typePoste", type="string", example="CDI"),
 *                 @OA\Property(property="typeTravail", type="string", example="Temps plein"),
 *                 @OA\Property(property="heureTravail", type="string", example="40 heures/semaine"),
 *                 @OA\Property(property="niveauExperience", type="string", example="2 ans"),
 *                 @OA\Property(property="niveauEtude", type="string", example="Licence"),
 *                 @OA\Property(property="pays", type="string", example="Tunisie"),
 *                 @OA\Property(property="ville", type="string", example="Tunis"),
 *                 @OA\Property(property="societe", type="string", example="TechCorp"),
 *                 @OA\Property(property="domaine", type="string", example="Informatique"),
 *                 @OA\Property(property="responsabilite", type="string", example="Développement de sites web."),
 *                 @OA\Property(property="experience", type="string", example="Expérience en développement web avec React et Node.js.")
 *             )
 *         )
 *     },
 *     @OA\Response(
 *         response=201,
 *         description="Offre ajoutée avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Offre ajoutée avec succès"),
 *             @OA\Property(property="offre", ref="#/components/schemas/Offre")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur de validation des données",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides.")
 *         )
 *     )
 * )
 */ 
public function ajoutOffre(Request $request)
{
    $request->validate([
        'departement' => 'required|string|max:255',
        'poste' => 'required|string|max:255',
        'description' => 'required|string',
        'dateExpiration' => 'required|date|after:today',
        'typePoste' => 'required|string|max:255',
        'typeTravail' => 'required|string|max:255',
        'heureTravail' => 'required|string|max:255',
        'niveauExperience' => 'required|string|max:255',
        'niveauEtude' => 'required|string|max:255',
        'pays' => 'required|string|max:255',
        'ville' => 'required|string|max:255',
        'societe' => 'required|string|max:255',
        'domaine' => 'required|string|max:255',
        'responsabilite' => 'required|string',
        'experience' => 'required|string',
        'matching' => 'nullable|numeric|min:0|max:100',
        'poids_ouverture' => 'required|numeric|min:2',
        'poids_conscience' => 'required|numeric|min:2',
        'poids_extraversion' => 'required|numeric|min:2',
        'poids_agreabilite' => 'required|numeric|min:2',
        'poids_stabilite' => 'required|numeric|min:2',
    ]);

    // Vérifier que la somme des poids est égale à 15
    $somme_poids = $request->poids_ouverture + $request->poids_conscience + 
                   $request->poids_extraversion + $request->poids_agreabilite + 
                   $request->poids_stabilite;
    
    if ($somme_poids != 15) {
        return response()->json([
            'error' => 'La somme des poids des traits de personnalité doit être égale à 15'
        ], 422);
    }

    $offre = Offre::create([
        'departement' => $request->departement,
        'poste' => $request->poste,
        'description' => $request->description,
        'datePublication' => now(), // Date du jour
        'dateExpiration' => $request->dateExpiration,
        'valider' => false, // Par défaut, l'offre n'est pas validée
        'typePoste' => $request->typePoste,
        'typeTravail' => $request->typeTravail,
        'heureTravail' => $request->heureTravail,
        'niveauExperience' => $request->niveauExperience,
        'niveauEtude' => $request->niveauEtude,
        'pays' => $request->pays,
        'ville' => $request->ville,
        'societe' => $request->societe,
        'domaine' => $request->domaine,
        'responsabilite' => $request->responsabilite,
        'experience' => $request->experience,
        'matching' => $request->matching ?? 0,
        'poids_ouverture' => $request->poids_ouverture,
        'poids_conscience' => $request->poids_conscience,
        'poids_extraversion' => $request->poids_extraversion,
        'poids_agreabilite' => $request->poids_agreabilite,
        'poids_stabilite' => $request->poids_stabilite,
    ]);

    // Get the authenticated user (recruiter)
    $recruiter = auth()->user();
    
    // Create notifications for all admins
    $admins = User::where('role', 'admin')->get();
    foreach ($admins as $admin) {
        Notification::create([
            'type' => 'new_job_offer',
            'message' => "Nouvelle offre d'emploi ajoutée: {$request->poste} chez {$request->societe}",
            'data' => [
                'offer_id' => $offre->id,
                'position' => $offre->poste,
                'company' => $offre->societe,
                'department' => $offre->departement,
                'recruiter_id' => $recruiter->id,
                'recruiter_name' => $recruiter->nom . ' ' . $recruiter->prenom,
            ],
            'user_id' => $admin->id,
            'read' => false,
        ]);
    }

    return response()->json([
        'message' => 'Offre ajoutée avec succès',
        'offre' => $offre
    ], 201);
}


 /**
 * @OA\Get(
 *     path="/api/AlloffresValide",
 *     tags={"Offre"},
 *     summary="Afficher toutes les offres validées",
 *     description="Récupère toutes les offres validées qui ne sont pas expirées.",
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres validées",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Offre")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur dans la récupération des offres",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Erreur lors de la récupération des offres.")
 *         )
 *     )
 * )
 */
    public function afficheOffreValide()
    {
        // Récupérer uniquement les offres validées
        $offres = Offre::where('valider', true)->where('dateExpiration', '>', now())  // Exclure les offres expirées
        ->get();
    
        return response()->json($offres);
    }

  /**
 * @OA\Get(
 *     path="/api/Alloffresnvalide",
 *     tags={"Offre"},
 *     summary="Afficher toutes les offres non validées",
 *     description="Récupère toutes les offres non validées qui ne sont pas expirées.",
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres non validées",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Offre")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur dans la récupération des offres",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Erreur lors de la récupération des offres.")
 *         )
 *     )
 * )
 */
    public function afficheOffreNValider()
    {
        $offres = Offre::where('valider', false)->where('dateExpiration', '>', now())  // Exclure les offres expirées
        ->get();
        return response()->json($offres);
    }



/**
 * @OA\Get(
 *     path="/api/offres-societe",
 *     tags={"Offre"},
 *     summary="Afficher les offres d'une société spécifique",
 *     description="Récupère toutes les offres non validées pour la société de l'utilisateur connecté.",
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres pour la société",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Offre")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Utilisateur non authentifié",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Utilisateur non authentifié")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Aucune offre trouvée pour la société",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Aucune offre trouvée pour cette société.")
 *         )
 *     )
 * )
 */

 public function offresParSociete() {
    // Récupérer l'utilisateur connecté
    $user = Auth::user();

    // Vérifier que l'utilisateur est authentifié
    if (!$user) {
        return response()->json(['error' => 'Utilisateur non authentifié'], 401);
    }

    // Récupérer les offres correspondant à la société de l'utilisateur
    $offres = Offre::where('societe', $user->nom_societe)->where('valider', 0)->get();

    // Retourner les offres au format JSON
    return response()->json($offres);
}


/**
 * @OA\Put(
 *     path="/api/validerOffre/{id}",
 *     tags={"Offre"},
 *     summary="Valider une offre",
 *     description="Permet de valider une offre d'emploi. Cette opération met à jour l'état de l'offre pour la marquer comme validée.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID de l'offre à valider",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Offre validée avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Offre validée avec succès."),
 *             @OA\Property(property="offre", ref="#/components/schemas/Offre")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Offre non trouvée",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Offre non trouvée.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Utilisateur non authentifié",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Utilisateur non authentifié")
 *         )
 *     )
 * )
 */


 public function validerOffre($id)
 {
     // Récupérer l'offre par son ID
     $offre = Offre::find($id);
 
     // Vérifier si l'offre existe
     if (!$offre) {
         return response()->json(['error' => 'Offre non trouvée.'], 404);
     }
 
     // Mettre à jour l'état de l'offre pour la marquer comme validée
     $offre->valider = true;
     $offre->save();
 
     // Trouver le recruteur qui a la même société que l'offre
     // Nous supposons que le recruteur a le rôle 'recruteur'
     $recruiter = User::where('nom_societe', $offre->societe)
                      ->where('role', 'recruteur')
                      ->first();
 
     if ($recruiter) {
         // Créer une notification pour le recruteur
         Notification::create([
             'type' => 'offer_validated',
             'message' => "Votre offre d'emploi '{$offre->poste}' a été validée",
             'data' => [
                 'offer_id' => $offre->id,
                 'position' => $offre->poste,
                 'department' => $offre->departement,
                 'company' => $offre->societe,
             ],
             'user_id' => $recruiter->id,
             'read' => false,
         ]);
     }
 
     // Retourner une réponse
     return response()->json([
         'message' => 'Offre validée avec succès.',
         'offre' => $offre
     ], 200);
 }


 

/**
 * @OA\Delete(
 *     path="/api/supprimerOffre/{id}",
 *     tags={"Offre"},
 *     summary="Supprimer une offre",
 *     description="Permet de supprimer une offre d'emploi en utilisant son ID.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID de l'offre à supprimer",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Offre supprimée avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Offre supprimée avec succès.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Offre non trouvée",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Offre non trouvée.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Utilisateur non authentifié",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Utilisateur non authentifié")
 *         )
 *     )
 * )
 */

    public function supprimerOffre($id)
    {
        
        // Récupérer l'offre par son ID
        $offre = Offre::find($id);

        // Vérifier si l'offre existe
        if (!$offre) {
            return response()->json(['error' => 'Offre non trouvée.'], 404);
        }

        // Supprimer l'offre
        $offre->delete();

        // Retourner une réponse de succès
        return response()->json([
            'message' => 'Offre supprimée avec succès.'
        ], 200);
    }


/**
 * @OA\Put(
 *     path="/api/offres-departement/{id}",
 *     tags={"Offre"},
 *     summary="Modifier une offre",
 *     description="Permet de modifier une offre d'emploi existante, sauf si elle est déjà validée.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID de l'offre à modifier",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="departement", type="string", maxLength=255),
 *             @OA\Property(property="poste", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="dateExpiration", type="string", format="date", example="2025-12-31"),
 *             @OA\Property(property="typePoste", type="string", maxLength=255),
 *             @OA\Property(property="typeTravail", type="string", maxLength=255),
 *             @OA\Property(property="heureTravail", type="string", maxLength=255),
 *             @OA\Property(property="niveauExperience", type="string", maxLength=255),
 *             @OA\Property(property="niveauEtude", type="string", maxLength=255),
 *             @OA\Property(property="pays", type="string", maxLength=255),
 *             @OA\Property(property="ville", type="string", maxLength=255),
 *             @OA\Property(property="societe", type="string", maxLength=255),
 *             @OA\Property(property="domaine", type="string", maxLength=255),
 *             @OA\Property(property="responsabilite", type="string"),
 *             @OA\Property(property="experience", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Offre modifiée avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Offre modifiée avec succès."),
 *             @OA\Property(property="offre", type="object", ref="#/components/schemas/Offre")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="L'offre ne peut pas être modifiée car elle est déjà validée",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Cette offre ne peut pas être modifiée car elle est déjà validée.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Offre non trouvée",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Offre non trouvée.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur lors de la modification de l'offre",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Une erreur est survenue lors de la modification de l'offre.")
 *         )
 *     )
 * )
 */

 public function modifierOffre(Request $request, $id)
 {
     try {
         // Trouver l'offre par son ID ou renvoyer une erreur 404 si elle n'existe pas
         $offre = Offre::findOrFail($id);
 
         // Vérifier si l'offre est déjà validée
         if ($offre->valider) {
             return response()->json(['error' => 'Cette offre ne peut pas être modifiée car elle est déjà validée.'], 400);
         }
 
         // Validation des données envoyées par la requête
         $validatedData = $request->validate([
             'departement' => 'nullable|string|max:255',
             'poste' => 'nullable|string|max:255',
             'description' => 'nullable|string',
             'dateExpiration' => 'nullable|date|after:today',
             'typePoste' => 'nullable|string|max:255',
             'typeTravail' => 'nullable|string|max:255',
             'heureTravail' => 'nullable|string|max:255',
             'niveauExperience' => 'nullable|string|max:255',
             'niveauEtude' => 'nullable|string|max:255',
             'pays' => 'nullable|string|max:255',
             'ville' => 'nullable|string|max:255',
             'societe' => 'nullable|string|max:255',
             'domaine' => 'nullable|string|max:255',
             'responsabilite' => 'nullable|string',
             'experience' => 'nullable|string',
             'matching' => 'nullable|numeric|min:0|max:100',
             'poids_ouverture' => 'nullable|numeric|min:2',
             'poids_conscience' => 'nullable|numeric|min:2',
             'poids_extraversion' => 'nullable|numeric|min:2',
             'poids_agreabilite' => 'nullable|numeric|min:2',
             'poids_stabilite' => 'nullable|numeric|min:2',
         ]);
 
         // Vérifier que la somme des poids est égale à 15 si au moins un poids est fourni
         if (isset($request->poids_ouverture) || isset($request->poids_conscience) || 
             isset($request->poids_extraversion) || isset($request->poids_agreabilite) || 
             isset($request->poids_stabilite)) {
             
             $somme_poids = 
                 (isset($request->poids_ouverture) ? $request->poids_ouverture : $offre->poids_ouverture) + 
                 (isset($request->poids_conscience) ? $request->poids_conscience : $offre->poids_conscience) + 
                 (isset($request->poids_extraversion) ? $request->poids_extraversion : $offre->poids_extraversion) + 
                 (isset($request->poids_agreabilite) ? $request->poids_agreabilite : $offre->poids_agreabilite) + 
                 (isset($request->poids_stabilite) ? $request->poids_stabilite : $offre->poids_stabilite);
             
             if ($somme_poids != 15) {
                 return response()->json([
                     'error' => 'La somme des poids des traits de personnalité doit être égale à 15'
                 ], 422);
             }
         }
 
         // Mise à jour des champs fournis par la requête
         $offre->update($validatedData);
 
         return response()->json([
             'message' => 'Offre modifiée avec succès.',
             'offre' => $offre
         ], 200);
 
     } catch (\Exception $e) {
         return response()->json([
             'error' => 'Une erreur est survenue lors de la modification de l\'offre.',
             'details' => $e->getMessage()
         ], 500);
     }
 }
    

/**
 * @OA\Put(
 *     path="/api/prolonger-offre/{id}",
 *     tags={"Offre"},
 *     summary="Prolonger la date d'expiration d'une offre",
 *     description="Permet de prolonger la date d'expiration d'une offre validée, à condition que la nouvelle date soit postérieure à aujourd'hui.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID de l'offre à prolonger",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="dateExpiration", type="string", format="date", example="2025-12-31")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="La date d'expiration de l'offre a été prolongée avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="La date d'expiration de l'offre a été prolongée avec succès."),
 *             @OA\Property(property="offre", type="object", ref="#/components/schemas/Offre")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur de validation de la date d'expiration ou l'offre n'est pas validée",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="La date d'expiration doit être postérieure à aujourd'hui.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Offre non trouvée",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Offre non trouvée.")
 *         )
 *     )
 * )
 */

    public function prolongerOffre(Request $request, $id)
    {
        // Validation de la date d'expiration
        $validator = Validator::make($request->all(), [
            'dateExpiration' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'La date d\'expiration doit être postérieure à aujourd\'hui.',
                'details' => $validator->errors()
            ], 400);
        }

        // Récupération de l'offre
        $offre = Offre::find($id);

        // Vérification de l'existence de l'offre
        if (!$offre) {
            return response()->json([
                'error' => 'Offre non trouvée.'
            ], 404);
        }

        // Vérification que l'offre est validée
        if (!$offre->valider) {
            return response()->json([
                'error' => 'Seules les offres validées peuvent être prolongées.'
            ], 400);
        }

        // Mise à jour de la date d'expiration uniquement
        $offre->dateExpiration = $request->dateExpiration;
        $offre->save();

        return response()->json([
            'message' => 'La date d\'expiration de l\'offre a été prolongée avec succès.',
            'offre' => $offre
        ], 200);
    }



/**
 * @OA\Get(
 *     path="/api/AlloffresExpiree",
 *     tags={"Offre"},
 *     summary="Afficher les offres expirées",
 *     description="Récupère toutes les offres dont la date d'expiration est passée.",
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres expirées récupérée avec succès",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Offre")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Aucune offre expirée trouvée",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Aucune offre expirée trouvée.")
 *         )
 *     )
 * )
 */
public function afficheOffreExpiree()
{
    // Récupérer uniquement les offres dont la date d'expiration est passée
    $offres = Offre::where('dateExpiration', '<', now())->get();
    
    // Retourner les offres expirées au format JSON
    return response()->json($offres);
}



/**
 * @OA\Get(
 *     path="/api/offres-expirees-societe",
 *     tags={"Offre"},
 *     summary="Afficher les offres expirées pour la société de l'utilisateur connecté",
 *     description="Récupère toutes les offres expirées appartenant à la société de l'utilisateur connecté.",
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres expirées pour la société de l'utilisateur récupérée avec succès",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Offre")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="La société de l'utilisateur n'est pas définie",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="La société de l'utilisateur n'est pas définie.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Aucune offre expirée trouvée pour la société",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Aucune offre expirée trouvée.")
 *         )
 *     )
 * )
 */

 public function afficheOffreExpireeRec()
 {
     // Récupérer l'utilisateur connecté
     $user = Auth::user();
 
     // Vérifier si l'utilisateur a une société associée
     if (!$user || !$user->nom_societe) {
         return response()->json(['error' => 'La société de l\'utilisateur n\'est pas définie.'], 400);
     }
 
     // Récupérer les offres expirées appartenant à la société de l'utilisateur
     $offres = Offre::where('societe', $user->nom_societe)
                    ->where('dateExpiration', '<', now())  // Vérifier que la date d'expiration est passée
                    ->get();
 
     return response()->json($offres);
 }

/**
 * @OA\Get(
 *     path="/api/offres-candidat",
 *     tags={"Offre"},
 *     summary="Afficher les offres valides et non expirées pour les candidats",
 *     description="Récupère toutes les offres qui sont validées et dont la date d'expiration est future. Ajoute une clé 'statut' pour indiquer si l'offre est urgente ou normale.",
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres valides et non expirées avec un statut dynamique ajouté",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="poste", type="string", example="Développeur web"),
 *                 @OA\Property(property="departement", type="string", example="Informatique"),
 *                 @OA\Property(property="societe", type="string", example="TechCorp"),
 *                 @OA\Property(property="dateExpiration", type="string", format="date-time", example="2025-04-15T00:00:00Z"),
 *                 @OA\Property(property="statut", type="string", example="normal"),
 *                 @OA\Property(property="valider", type="boolean", example=true),
 *                 @OA\Property(property="description", type="string", example="Développer des applications web...")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Aucune offre disponible ou expirée",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Aucune offre valide disponible.")
 *         )
 *     )
 * )
 */

public function afficherOffreCandidat()
{
    // Récupérer les offres qui ne sont pas encore expirées et qui sont validées
    $offres = Offre::where('dateExpiration', '>', now())
        ->where('valider', 1)
        ->get();

    // Parcourir les offres pour ajouter une clé dynamique "statut" dans la réponse
    $offres->transform(function ($offre) {
        // Utilisation de Carbon pour manipuler la date d'expiration
        $expiration = \Carbon\Carbon::parse($offre->dateExpiration);
        
        // Calculer la différence entre la date actuelle et la date d'expiration
        $diffInDays = now()->diffInDays($expiration, false);
        
        // Ajouter une clé dynamique 'statut' avec la valeur 'urgent' ou 'normal'
        $offre->statut = $diffInDays <= 3 ? 'urgent' : 'normal';

        return $offre;
    });

    // Retourner les offres avec la clé 'statut' dynamique
    return response()->json($offres);
}
/**
 * @OA\Get(
 *     path="/api/villes-domaines",
 *     tags={"Offre"},
 *     summary="Récupérer les villes et domaines distincts des offres validées",
 *     description="Récupère les villes et domaines distincts des offres validées pour permettre aux candidats de filtrer les offres.",
 *     @OA\Response(
 *         response=200,
 *         description="Liste des villes et domaines distincts",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="villes", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="domaines", type="array", @OA\Items(type="string"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Aucune offre validée trouvée",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Aucune offre valide disponible.")
 *         )
 *     )
 * )
 */
public function afficheVillesEtDomainesDistincts()
{
    $villes = Offre::where('valider', 1)->distinct()->pluck('ville');
    $domaines = Offre::where('valider', 1)->distinct()->pluck('domaine');

    return response()->json([
        'villes' => $villes,
        'domaines' => $domaines
    ]);
}

/**
 * @OA\Get(
 *     path="/api/offresRecherche",
 *     tags={"Offre"},
 *     summary="Recherche des offres d'emploi avec des filtres",
 *     description="Permet de rechercher des offres d'emploi validées en utilisant divers filtres comme le poste, la ville, le domaine, etc.",
 *     @OA\Parameter(
 *         name="poste",
 *         in="query",
 *         required=false,
 *         description="Filtrer les offres par poste",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="ville",
 *         in="query",
 *         required=false,
 *         description="Filtrer les offres par ville",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="domaine",
 *         in="query",
 *         required=false,
 *         description="Filtrer les offres par domaine",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="typePoste",
 *         in="query",
 *         required=false,
 *         description="Filtrer les offres par type de poste (ex: CDI, CDD)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="datePublication",
 *         in="query",
 *         required=false,
 *         description="Filtrer les offres par date de publication",
 *         @OA\Schema(type="string", enum={"derniere_heure", "24_heure", "derniers_7_jours"})
 *     ),
 *     @OA\Parameter(
 *         name="niveauExperience",
 *         in="query",
 *         required=false,
 *         description="Filtrer les offres par niveau d'expérience",
 *         @OA\Schema(type="string", enum={"tous", "2ans", "5ans", "7ans", "+10ans", "Sans expérience"})
 *     ),
 *     @OA\Parameter(
 *         name="typeTravail",
 *         in="query",
 *         required=false,
 *         description="Filtrer les offres par type de travail",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres correspondant aux critères de recherche",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Offre")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Paramètres de recherche invalides",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Requête mal formée.")
 *         )
 *     )
 * )
 */
public function rechercheOffresss(Request $request) {
    // Start with a base query for validated offers
    $query = Offre::where('valider', 1);

    // Filter by position
    if ($request->has('poste')) {
        $query->where('poste', 'like', '%' . $request->input('poste') . '%');
    }

    // Filter by city
    if ($request->has('ville')) {
        $query->where('ville', 'like', '%' . $request->input('ville') . '%');
    }

    // Filter by domain
    if ($request->has('domaine')) {
        $query->where('domaine', 'like', '%' . $request->input('domaine') . '%');
    }

    // Filter by job type
    if ($request->has('typePoste')) {
        $typePoste = explode(',', $request->input('typePoste'));
        $query->whereIn('typePoste', $typePoste);
    }

    // Filter by publication date
    if ($request->has('datePublication')) {
        $datePublication = $request->input('datePublication');
        switch ($datePublication) {
            case 'derniere_heure':
                $query->where('created_at', '>=', Carbon::now()->subHour());
                break;
            case '24_heure':
                $query->where('created_at', '>=', Carbon::now()->subDay());
                break;
            case 'derniers_7_jours':
                $query->where('created_at', '>=', Carbon::now()->subDays(7));
                break;
        }
    }

    // Filter by experience level
    if ($request->has('niveauExperience')) {
        $niveauExperience = $request->input('niveauExperience');
        
        if ($niveauExperience === '+10ans' || $niveauExperience === 'plus_10ans') {
            // For "More than 10 years"
            $query->where('niveauExperience', '+10ans');
        } elseif ($niveauExperience === 'Sans expérience' || $niveauExperience === 'sans_experience') {
            // For "No experience"
            $query->where('niveauExperience', 'Sans expérience');
        } elseif ($niveauExperience !== 'tous') {
            // For specific levels (2ans, 5ans, 7ans)
            $query->where('niveauExperience', $niveauExperience);
        }
    }
    // Also check the niveauExperience_min parameter (for compatibility)
    elseif ($request->has('niveauExperience_min')) {
        $query->whereIn('niveauExperience', ['4ans', '5ans', '6ans', '7ans', '8ans', '9ans', '10ans', '+10ans']);
    }

    // Filter by work type
    if ($request->has('typeTravail')) {
        $query->where('typeTravail', $request->input('typeTravail'));
    }

    // Order by creation date (newest first - LIFO)
    $query->orderBy('created_at', 'desc');

    $offres = $query->get();
    return response()->json($offres);
}


/**
 * @OA\Post(
 *     path="/api/recherche-acceuil",
 *     tags={"Offre"},
 *     summary="Recherche des offres d'emploi par domaine et département",
 *     description="Permet de rechercher des offres validées en fonction du domaine et du département",
 *     @OA\Parameter(
 *         name="domaine",
 *         in="query",
 *         required=false,
 *         description="Filtrer les offres par domaine",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="departement",
 *         in="query",
 *         required=false,
 *         description="Filtrer les offres par département",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres correspondant aux critères de recherche",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Offre")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Paramètres de recherche invalides",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Requête mal formée.")
 *         )
 *     )
 * )
 */

public function rechercheAcceuil(Request $request)
{
    $query = Offre::where('valider', 1); // Filtrer uniquement les offres validées

    if ($request->has('domaine')) {
        $query->where('domaine', 'like', '%' . $request->input('domaine') . '%');
    }
    if ($request->has('departement')) {
        $query->where('departement', 'like', '%' . $request->input('departement') . '%');
    }

    $offres = $query->get();
    return response()->json($offres);
}
/**
 * @OA\Get(
 *     path="/api/departements-domaines",
 *     tags={"Offre"},
 *     summary="Obtenir la liste des départements et domaines distincts",
 *     description="Retourne les départements et domaines distincts des offres validées",
 *     @OA\Response(
 *         response=200,
 *         description="Liste des départements et domaines distincts",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="departements", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="domaines", type="array", @OA\Items(type="string"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Requête mal formée",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Requête mal formée.")
 *         )
 *     )
 * )
 */
public function afficheDepartementsEtDomainesDistincts()
{
    $departements = Offre::where('valider', 1)->distinct()->pluck('departement');
    $domaines = Offre::where('valider', 1)->distinct()->pluck('domaine');

    return response()->json([
        'departements' => $departements,
        'domaines' => $domaines
    ]);
}
/**
 * @OA\Get(
 *     path="/api/offreDetail/{id}",
 *     tags={"Offre"},
 *     summary="Obtenir les détails d'une offre",
 *     description="Retourne les détails d'une offre d'emploi spécifique en fonction de son ID",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'offre",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Détails de l'offre retournés avec succès",
 *         @OA\JsonContent(ref="#/components/schemas/Offre")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Offre non trouvée",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Offre non trouvée")
 *         )
 *     )
 * )
 */
public function showDetail($id)
{
    // Trouver l'offre par son ID
    $offre = Offre::find($id);

    // Vérifier si l'offre existe
    if (!$offre) {
        return response()->json(['message' => 'Offre non trouvée'], 404);
    }

    // Retourner les données de l'offre en JSON
    return response()->json($offre);
}
  
/**
 * @OA\Get(
 *     path="/api/offres_domaine/{domaine}",
 *     tags={"Offre"},
 *     summary="Obtenir les offres par domaine",
 *     description="Retourne les offres d'emploi disponibles pour un domaine donné",
 *     @OA\Parameter(
 *         name="domaine",
 *         in="path",
 *         required=true,
 *         description="Domaine des offres",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres retournée avec succès",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Offre"))
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Aucune offre trouvée pour ce domaine",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Aucune offre trouvée pour ce département")
 *         )
 *     )
 * )
 */
public function getByDepartement($domaine)
{
    // Récupérer les offres du département donné
    $offres = Offre::where('domaine', $domaine)->get();

    // Vérifier si des offres existent
    if ($offres->isEmpty()) {
        return response()->json(['message' => 'Aucune offre trouvée pour ce département'], 404);
    }

    // Retourner les offres en JSON
    return response()->json($offres);
}
/**
 * @OA\Get(
 *     path="/api/offres-recruteur-valides",
 *     tags={"Offre"},
 *     summary="Récupérer les offres validées pour un recruteur",
 *     description="Retourne les offres validées et non expirées associées à la société du recruteur authentifié.",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres validées et non expirées pour la société du recruteur",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Offre"))
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Aucune société associée à l'utilisateur",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Aucune société associée à cet utilisateur.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Aucune offre trouvée pour la société de l'utilisateur",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Aucune offre trouvée pour cette société.")
 *         )
 *     )
 * )
 */
public function offreValideRecruteur(Request $request)
{
    $user = $request->user(); // Récupérer l'utilisateur authentifié
    
    // Vérifier si l'utilisateur a une société associée
    if (!$user || !$user->nom_societe) {
        return response()->json(['message' => 'Aucune société associée à cet utilisateur.'], 403);
    }

    // Récupérer les offres validées, non expirées et appartenant à la société du recruteur
    $offres = Offre::where('valider', true)
        ->where('dateExpiration', '>', now())
        ->where('societe', $user->nom_societe) // Filtrer par le nom de la société
        ->get();

    return response()->json($offres);
}
/**
 * @OA\Get(
 *     path="/api/recherche-offre/{poste}",
 *     tags={"Offre"},
 *     summary="Rechercher des offres par poste",
 *     description="Retourne les offres qui correspondent à un poste donné.",
 *     @OA\Parameter(
 *         name="poste",
 *         in="path",
 *         description="Nom du poste à rechercher",
 *         required=true,
 *         @OA\Schema(
 *             type="string",
 *             example="Développeur"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des offres correspondant au poste",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Offre"))
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Aucune offre trouvée pour ce poste",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Aucune offre trouvée pour ce poste.")
 *         )
 *     )
 * )
 */
public function rechercheOffre($poste)
{
    return Offre::where('poste', 'like', '%' . $poste . '%')->get();
}




}