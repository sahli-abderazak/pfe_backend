<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\Temoignage;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TemoignageController extends Controller
{

        /**
 * @OA\Post(
 *     path="/api/temoiniage",
 *     summary="Ajouter un témoignage",
 *     description="Permet à un utilisateur d'ajouter un témoignage, en attente de validation par un administrateur.",
 *     operationId="storeTestimonial",
 *     tags={"Témoignage"},
 *     requestBody={
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"nom", "email", "temoignage"},
 *                 @OA\Property(property="nom", type="string", description="Nom de l'utilisateur"),
 *                 @OA\Property(property="email", type="string", description="Email de l'utilisateur"),
 *                 @OA\Property(property="temoignage", type="string", description="Le contenu du témoignage")
 *             )
 *         )
 *     },
 *     responses={
 *         @OA\Response(
 *             response=201,
 *             description="Témoignage ajouté avec succès",
 *             @OA\JsonContent(
 *                 type="object",
 *                 @OA\Property(property="message", type="string"),
 *                 @OA\Property(
 *                     property="temoignage",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="nom", type="string"),
 *                     @OA\Property(property="email", type="string"),
 *                     @OA\Property(property="temoignage", type="string"),
 *                     @OA\Property(property="valider", type="boolean")
 *                 )
 *             )
 *         ),
 *         @OA\Response(response=400, description="Erreur de validation des données")
 *     }
 * )
 */
public function store(Request $request)
{
    $user = $request->user(); // Récupère l'utilisateur connecté

    // Validation des données
    $request->validate([
        'temoignage' => 'required|string',
        'rate' => 'nullable|integer|min:1|max:5',
    ]);

    // Création du témoignage
    $temoignage = Temoignage::create([
        'nom' => $user->nom_societe,
        'email' => $user->email,
        'temoignage' => $request->temoignage,
        'valider' => false,
        'rate' => $request->rate,
    ]);

    // Créer une notification pour les admins
    $admins = User::where('role', 'admin')->get();
    foreach ($admins as $admin) {
        Notification::create([
            'type' => 'new_testimonial',
            'message' => "Nouveau témoignage de {$user->name} en attente de validation",
            'data' => [
                'testimonial_id' => $temoignage->id,
                'name' => $temoignage->nom,
                'email' => $temoignage->email,
                'testimonial_preview' => substr($temoignage->temoignage, 0, 100) . (strlen($temoignage->temoignage) > 100 ? '...' : ''),
            ],
            'user_id' => $admin->id,
            'read' => false,
        ]);
    }

    return response()->json([
        'message' => 'Témoignage ajouté avec succès, en attente de validation !',
        'temoignage' => $temoignage
    ], 201);
}


    /**
 * @OA\Get(
 *     path="/api/temoignagesValides",
 *     summary="Récupérer tous les témoignages validés",
 *     description="Cette fonction récupère tous les témoignages validés par un administrateur.",
 *     operationId="getValidTestimonials",
 *     tags={"Témoignage"},
 *     responses={
 *         @OA\Response(
 *             response=200,
 *             description="Liste des témoignages validés",
 *             @OA\JsonContent(
 *                 type="object",
 *                 @OA\Property(property="message", type="string"),
 *                 @OA\Property(
 *                     property="temoignages",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer"),
 *                         @OA\Property(property="nom", type="string"),
 *                         @OA\Property(property="email", type="string"),
 *                         @OA\Property(property="temoignage", type="string"),
 *                         @OA\Property(property="valider", type="boolean")
 *                     )
 *                 )
 *             )
 *         ),
 *         @OA\Response(response=404, description="Aucun témoignage validé trouvé")
 *     }
 * )
 */
public function showTemoin()
{
    // Récupérer uniquement les témoignages validés
    $temoignages = Temoignage::where('valider', true)->get();

    // Retourner une réponse JSON
    return response()->json([
        'message' => 'Liste des témoignages validés',
        'temoignages' => $temoignages
    ], 200);
}
/**
 * @OA\Get(
 *     path="/api/temoiniages_admin",
 *     summary="Récupérer tous les témoignages",
 *     description="Permet à un utilisateur authentifié de récupérer tous les témoignages.",
 *     operationId="getAllTestimonials",
 *     tags={"Témoignage"},
 *     security={
 *         {"bearerAuth": {}}
 *     },
 *     responses={
 *         @OA\Response(
 *             response=200,
 *             description="Liste de tous les témoignages",
 *             @OA\JsonContent(
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="nom", type="string"),
 *                     @OA\Property(property="email", type="string"),
 *                     @OA\Property(property="temoignage", type="string"),
 *                     @OA\Property(property="valider", type="boolean")
 *                 )
 *             )
 *         ),
 *         @OA\Response(response=401, description="Utilisateur non authentifié"),
 *         @OA\Response(response=404, description="Aucun témoignage trouvé")
 *     }
 * )
 */
public function getAllTemoiniages()
{
    // Vérifier si l'utilisateur est authentifié
    if (!Auth::check()) {
        return response()->json(['error' => 'Utilisateur non authentifié.'], Response::HTTP_UNAUTHORIZED);
    }

    // Récupérer tous les témoignages
    $temoiniages = Temoignage::all();

    return response()->json($temoiniages, Response::HTTP_OK);
}
/**
 * @OA\Put(
 *     path="/api/temoiniages/valider/{id}",
 *     summary="Valider un témoignage",
 *     description="Permet à un utilisateur authentifié de valider un témoignage.",
 *     operationId="validateTestimonial",
 *     tags={"Témoignage"},
 *     security={
 *         {"bearerAuth": {}}
 *     },
 *     parameters={
 *         @OA\Parameter(
 *             name="id",
 *             in="path",
 *             required=true,
 *             description="ID du témoignage",
 *             @OA\Schema(type="integer")
 *         )
 *     },
 *     responses={
 *         @OA\Response(
 *             response=200,
 *             description="Témoignage validé avec succès",
 *             @OA\JsonContent(
 *                 type="object",
 *                 @OA\Property(property="message", type="string"),
 *                 @OA\Property(
 *                     property="temoiniage",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="nom", type="string"),
 *                     @OA\Property(property="email", type="string"),
 *                     @OA\Property(property="temoignage", type="string"),
 *                     @OA\Property(property="valider", type="boolean")
 *                 )
 *             )
 *         ),
 *         @OA\Response(response=401, description="Utilisateur non authentifié"),
 *         @OA\Response(response=404, description="Témoignage introuvable")
 *     }
 * )
 */
public function validerTemoiniage($id)
{
    // Vérifier si l'utilisateur est authentifié
    if (!Auth::check()) {
        return response()->json(['error' => 'Utilisateur non authentifié.'], Response::HTTP_UNAUTHORIZED);
    }

    // Trouver le témoignage par ID
    $temoiniage = Temoignage::find($id);

    // Vérifier si le témoignage existe
    if (!$temoiniage) {
        return response()->json(['error' => 'Témoignage introuvable.'], Response::HTTP_NOT_FOUND);
    }

    // Mettre à jour le champ "valider" à true
    $temoiniage->valider = true;
    $temoiniage->save();

    return response()->json(['message' => 'Témoignage validé avec succès.', 'temoiniage' => $temoiniage], Response::HTTP_OK);
}

/**
 * @OA\Delete(
 *     path="/api/temoignageSupp/{id}",
 *     summary="Supprimer un témoignage",
 *     description="Permet de supprimer un témoignage avec l'ID spécifié.",
 *     operationId="deleteTestimonial",
 *     tags={"Témoignage"},
 *     parameters={
 *         @OA\Parameter(
 *             name="id",
 *             in="path",
 *             required=true,
 *             description="ID du témoignage",
 *             @OA\Schema(type="integer")
 *         )
 *     },
 *     responses={
 *         @OA\Response(
 *             response=200,
 *             description="Témoignage supprimé avec succès",
 *             @OA\JsonContent(
 *                 type="object",
 *                 @OA\Property(property="message", type="string")
 *             )
 *         ),
 *         @OA\Response(response=404, description="Témoignage non trouvé")
 *     }
 * )
 */
public function deleteTemoignage($id)
{
    // Récupérer le témoignage par son ID
    $temoignage = Temoignage::find($id);

    if (!$temoignage) {
        return response()->json(['message' => 'Témoignage non trouvé'], 404);
    }

    // Supprimer le témoignage
    $temoignage->delete();

    return response()->json(['message' => 'Témoignage supprimé avec succès'], 200);
}


}
