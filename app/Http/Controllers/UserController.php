<?php

namespace App\Http\Controllers;

use App\Models\Candidat;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{

/**
 * @OA\Get(
 *     path="/api/recruteurs_acceuil",
 *     summary="Récupérer les recruteurs non archivés",
 *     description="Obtenir une liste de tous les recruteurs actifs.",
 *     tags={"Recruteurs"},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des recruteurs",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="nom_societe", type="string", example="ABC Corp"),
 *                 @OA\Property(property="image", type="string", example="http://example.com/storage/images/recruiter.jpg"),
 *                 @OA\Property(property="domaine_activite", type="string", example="IT"),
 *                 @OA\Property(property="apropos", type="string", example="About the company"),
 *                 @OA\Property(property="lien_site_web", type="string", example="http://example.com")
 *             )
 *         )
 *     )
 * )
 */
    public function recruteurAcceuil()
    {
        $recruteurs = User::where('role', 'recruteur')
                          ->where('archived', 0)
                          ->select(   'nom_societe', 'image','domaine_activite','apropos') // Sélectionner uniquement les champs nécessaires
                          ->get();
    
        foreach ($recruteurs as $recruteur) {
            $recruteur->image = $recruteur->image ? asset('storage/' . $recruteur->image) : null;
        }
    
        return response()->json($recruteurs);
    }
   /**
 * @OA\Get(
 *     path="/api/users",
 *     summary="Récupérer tous les recruteurs non archivés",
 *     description="Obtenir une liste de tous les recruteurs actifs.",
 *     tags={"Utilisateurs"},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des recruteurs",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="email", type="string", example="user@example.com"),
 *                 @OA\Property(property="image", type="string", example="http://example.com/storage/images/user.jpg"),
 *                 @OA\Property(property="cv", type="string", example="http://example.com/storage/cv/user.pdf"),
 *                 @OA\Property(property="role", type="string", example="recruteur"),
 *                 @OA\Property(property="archived", type="boolean", example=false)
 *             )
 *         )
 *     )
 * )
 */
    public function index()
{
    $recruteurs = User::where('role', 'recruteur')
                      ->where('archived', 0)
                      ->get();

    foreach ($recruteurs as $recruteur) {
        $recruteur->image = $recruteur->image ? asset('storage/' . $recruteur->image) : null;
    }

    return response()->json($recruteurs);
}
  
 /**
 * @OA\Put(
 *     path="/api/users/unarchive/{id}",
 *     summary="Désarchiver un utilisateur",
 *     description="Désarchiver un utilisateur par son ID",
 *     tags={"Utilisateurs"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Utilisateur désarchivé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Utilisateur désarchivé avec succès")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Utilisateur non trouvé",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Utilisateur non trouvé.")
 *         )
 *     )
 * )
 */
    public function unarchiveUser($id)
    {
        $user = User::find($id);
    
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
        }
    
        $user->archived = false;
        $user->save();
    
        return response()->json(['message' => 'Utilisateur désarchivé avec succès.'], 200);
    }
   /**
 * @OA\Put(
 *     path="/api/users/archive/{id}",
 *     summary="Archiver un utilisateur",
 *     description="Archiver un utilisateur par son ID",
 *     tags={"Utilisateurs"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Utilisateur archivé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Utilisateur archivé avec succès")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Utilisateur non trouvé",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Utilisateur non trouvé.")
 *         )
 *     )
 * )
 */
    public function archiveUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
        }

        $user->archived = true;
        $user->save();

        return response()->json(['message' => 'Utilisateur archivé avec succès.'], 200);
    }
/**
 * @OA\Get(
 *     path="/api/users/archived",
 *     summary="Récupérer tous les utilisateurs archivés",
 *     description="Obtenir une liste de tous les utilisateurs archivés.",
 *     tags={"Utilisateurs"},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des utilisateurs archivés",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="email", type="string", example="user@example.com"),
 *                 @OA\Property(property="cv", type="string", example="http://example.com/storage/cv/user.pdf"),
 *                 @OA\Property(property="role", type="string", example="recruteur"),
 *                 @OA\Property(property="archived", type="boolean", example=true)
 *             )
 *         )
 *     )
 * )
 */

    public function getArchivedUsers()
    {
        $archivedUsers = User::where('archived', true)->get();
        return response()->json($archivedUsers, 200);
    }


    
/**
 * @OA\Get(
 *     path="/api/user/info",
 *     summary="Récupérer les informations de l'utilisateur connecté",
 *     description="Retourne les informations de l'utilisateur actuellement authentifié.",
 *     tags={"Utilisateurs"},
 *     security={{ "sanctum": {} }},
 *     @OA\Response(
 *         response=200,
 *         description="Informations de l'utilisateur",
 *         @OA\JsonContent(
 *             @OA\Property(property="nom_societe", type="string", example="ABC Corp"),
 *             @OA\Property(property="image", type="string", example="http://example.com/storage/images/user.jpg")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non autorisé",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Non authentifié")
 *         )
 *     )
 * )
 */ public function getCurrentUserInfo()
 {
     $user = Auth::user();
     
     return response()->json([
        'nom_societe' => $user->nom_societe ?? null,
         'image' => $user->image ? asset('storage/' . $user->image) : null,
     ]);

 }
/**
     * @OA\Get(
     *     path="/api/recherche-candidat-archive",
     *     summary="Rechercher des candidats archivés",
     *     description="Rechercher des candidats archivés en fonction du nom et du prénom.",
     *     tags={"Candidats"},
     *     @OA\Parameter(
     *         name="nom",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", example="Dupont")
     *     ),
     *     @OA\Parameter(
     *         name="prenom",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", example="Jean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des candidats archivés trouvés",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Candidat")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun candidat trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Aucun candidat trouvé.")
     *         )
     *     )
     * )
     */

 public function rechercheCandidatArchive($nom = null, $prenom = null)
{
    $query = Candidat::where('archived', 1);

    if (!empty($nom)) {
        $query->where('nom', 'like', '%' . $nom . '%');
    }
    if (!empty($prenom)) {
        $query->where('prenom', 'like', '%' . $prenom . '%');
    }

    return $query->get();
}


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
 * @OA\Get(
 *     path="/api/recruteurs-archives/recherche",
 *     summary="Rechercher des recruteurs archivés",
 *     description="Rechercher des recruteurs archivés par lettre.",
 *     tags={"Recruteurs"},
 *     @OA\Parameter(
 *         name="letter",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="string", example="A")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des recruteurs archivés",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/User")
 *         )
 *     )
 * )
 */
public function searchArchivedRecruiters(Request $request)
{
    $letter = $request->input('letter');

    $recruiters = User::where('archived', true)
        ->where('role', 'recruteur')
        ->where(function ($query) use ($letter) {
            $query->where('nom_societe', 'LIKE', "%$letter%");
        })
        ->get();

    return response()->json($recruiters);
}
/**
 * @OA\Get(
 *     path="/api/recherche-recruteur",
 *     summary="Rechercher des recruteurs",
 *     description="Rechercher des recruteurs par lettre.",
 *     tags={"Recruteurs"},
 *     @OA\Parameter(
 *         name="letter",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="string", example="A")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Liste des recruteurs",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/User")
 *         )
 *     )
 * )
 */
public function rechercheRecruteur($nomSociete = null)
{
    $query = User::query();

    if (!empty($nomSociete)) {
        $query->where('nom_societe', 'like', '%' . $nomSociete . '%');
    }

    return $query->get();
}
}