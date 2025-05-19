<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\RecruiterAdded;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;
use Illuminate\Support\Str;
use App\Models\Notification;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="API Documentation",
 *      description="Documentation de l'API pour la gestion des utilisateurs et l'authentification.",
 *      @OA\Contact(
 *          email="sahliabderazak530@gmail.com"
 *      ),
 *      @OA\License(
 *          name="MIT",
 *          url="https://opensource.org/licenses/MIT"
 *      )
 * )
 */

class AuthController extends Controller
{
/**
 * @OA\Get(
 *     path="/api/users/profile",
 *     summary="Afficher le profil utilisateur",
 *     description="Récupère les informations de l'utilisateur connecté.",
 *     tags={"Utilisateur"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Profil utilisateur récupéré avec succès.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="email", type="string", example="user@example.com"),
 *             @OA\Property(property="numTel", type="string", example="+216 12 345 678"),
 *             @OA\Property(property="adresse", type="string", example="Rue Hbib Bourguiba, Hammamet"),
 *             @OA\Property(property="image", type="string", nullable=true, example="https://example.com/storage/user.jpg"),
 *             @OA\Property(property="role", type="string", example="recruteur"),
 *             @OA\Property(property="nom_societe", type="string", example="Tech Solutions"),
 *             @OA\Property(property="apropos", type="string", example="Entreprise spécialisée en IT"),
 *             @OA\Property(property="lien_site_web", type="string", nullable=true, example="https://www.techsolutions.com"),
 *             @OA\Property(property="fax", type="string", nullable=true, example="+216 71 234 567"),
 *             @OA\Property(property="domaine_activite", type="string", example="Développement Web")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Non autorisé. Token invalide ou manquant."),
 *     @OA\Response(response=404, description="Utilisateur non trouvé.")
 * )
 */
public function showProfile(Request $request)
{
    $user = $request->user(); // Récupère l'utilisateur connecté

    if (!$user) {
        return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
    }

    // Récupère uniquement les champs nécessaires
    $userData = $user->only([
        'id', 'email', 'numTel', 'adresse', 'image', 'role', 'nom_societe',
        // Nouveaux champs
        'apropos', 'lien_site_web', 'fax', 'domaine_activite'
        // Suppression de: 'departement', 'nom', 'prenom', 'poste', 'cv'
    ]);

    // Ajoute l'URL de l'image si disponible
    $userData['image'] = $user->image ? asset('storage/' . $user->image) : null;

    return response()->json($userData, 200);
}


/**
 * @OA\Put(
 *     path="/api/user/update/{id}",
 *     summary="Mettre à jour le département et le poste d'un administrateur",
 *     description="Mise à jour des champs département et poste d'un utilisateur spécifique.",
 *     tags={"Utilisateur"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'utilisateur à mettre à jour",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="departement", type="string", nullable=true, example="Informatique"),
 *             @OA\Property(property="poste", type="string", nullable=true, example="Chef de projet")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Département et poste mis à jour avec succès.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Département et poste mis à jour avec succès.")
 *         )
 *     ),
 *     @OA\Response(response=404, description="Utilisateur non trouvé."),
 *     @OA\Response(response=422, description="Erreur de validation des données.")
 * )
 */
    public function updateAdmin(Request $request, $id)
{
    // Validation des données d'entrée
    $validatedData = $request->validate([
        'departement' => 'nullable|string',
        'poste' => 'nullable|string',
    ]);

    // Récupérer l'utilisateur par son ID
    $user = User::find($id);

    if (!$user) {
        return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
    }

    // Vérifier si le champ département est fourni
    if ($request->has('departement')) {
        $user->departement = $validatedData['departement'];
    }

    // Vérifier si le champ poste est fourni
    if ($request->has('poste')) {
        $user->poste = $validatedData['poste'];
    }

    // Sauvegarder les modifications dans la base de données
    $user->save();

    // Retourner une réponse de succès
    return response()->json(['message' => 'Département et poste mis à jour avec succès.'], 200);
}

/**
 * @OA\Post(
 *     path="/api/logout",
 *     summary="Déconnexion de l'utilisateur",
 *     description="Supprime le token d'accès actuel et déconnecte l'utilisateur.",
 *     tags={"Authentification"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Déconnexion réussie.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Logged out successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non authentifié.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Unauthenticated.")
 *         )
 *     )
 * )
 */

public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }


/**
 * @OA\Post(
 *     path="/api/login",
 *     summary="Connexion de l'utilisateur",
 *     description="Authentifie un utilisateur et génère un token d'accès.",
 *     tags={"Authentification"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *             @OA\Property(property="password", type="string", example="password123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Connexion réussie.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Login successful"),
 *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1..."),
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="email", type="string", example="user@example.com"),
 *                 @OA\Property(property="role", type="string", example="admin")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur de validation.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="object", example={"email": {"Le champ email est requis."}})
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Identifiants invalides.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Invalid credentials")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Compte inactif.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Votre compte est inactif. Veuillez vérifier votre compte.")
 *         )
 *     )
 * )
 */


 public function login(Request $request)
 {
     $validator = Validator::make($request->all(), [
         'email' => 'required|email',
         'password' => 'required',
     ]);
 
     if ($validator->fails()) {
         return response()->json(['error' => $validator->errors()], 400);
     }
 
     $user = User::where('email', $request->email)->first();
 
     // Vérification si l'utilisateur existe et si le mot de passe est correct
     if (!$user || !Hash::check($request->password, $user->password)) {
         return response()->json(['error' => 'Invalid credentials'], 401);
     }
 
     // Vérification si le compte est actif
     if (!$user->active) {
        return response()->json(['error' => 'Votre compte est inactif. Veuillez vérifier votre compte.'], 403);
    }
 
     $token = $user->createToken('backendPFE')->plainTextToken;
 
     return response()->json([
         'message' => 'Login successful',
         'token' => $token,
         'user' => $user,
     ], 200);
 }
 
 

/**
 * @OA\Post(
 *     path="/api/register",
 *     summary="Inscription d'un utilisateur",
 *     description="Crée un compte recruteur et envoie un email de vérification.",
 *     tags={"Authentification"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"email","password","numTel","adresse","role","image","nom_societe","domaine_activite"},
 *                 @OA\Property(property="email", type="string", format="email", example="recruteur@example.com"),
 *                 @OA\Property(property="password", type="string", format="password", example="password123"),
 *                 @OA\Property(property="numTel", type="string", example="+21612345678"),
 *                 @OA\Property(property="adresse", type="string", example="123 Avenue de l'Entreprise, Tunis"),
 *                 @OA\Property(property="role", type="string", example="recruteur"),
 *                 @OA\Property(property="image", type="string", format="binary"),
 *                 @OA\Property(property="nom_societe", type="string", example="Tech Solutions"),
 *                 @OA\Property(property="apropos", type="string", example="Une société innovante spécialisée en IT."),
 *                 @OA\Property(property="lien_site_web", type="string", format="url", example="https://www.techsolutions.com"),
 *                 @OA\Property(property="fax", type="string", example="+21671234567"),
 *                 @OA\Property(property="domaine_activite", type="string", example="Technologie et Développement")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Inscription réussie et email envoyé.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Registration successful and email sent!"),
 *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1..."),
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="id", type="integer", example=10),
 *                 @OA\Property(property="email", type="string", example="recruteur@example.com"),
 *                 @OA\Property(property="nom_societe", type="string", example="Tech Solutions"),
 *                 @OA\Property(property="role", type="string", example="recruteur"),
 *                 @OA\Property(property="active", type="boolean", example=false)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur de validation.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="object", example={"email": {"Le champ email est requis."}})
 *         )
 *     )
 * )
 */

 

 public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8',
        // Suppression de: 'nom', 'prenom', 'departement', 'poste', 'cv'
        'numTel' => 'required|string',
        'adresse' => 'required|string',
        'role' => 'required|string',
        'image' => 'required|file|mimes:jpeg,png,jpg|max:2048',
        'nom_societe' => 'required|string|unique:users,nom_societe',
        // Nouveaux champs
        'apropos' => 'nullable|string',
        'lien_site_web' => 'nullable|string|url',
        'fax' => 'nullable|string',
        'domaine_activite' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Stockage des fichiers
    $imagePath = $request->file('image')->store('images', 'public');
    // Suppression du stockage du CV

    // Générer un code de vérification unique
    $verificationCode = Str::random(6);

    // Création de l'utilisateur
    $user = User::create([
        'email' => $request->email,
        'password' => Hash::make($request->password),
        // Suppression de: 'departement', 'nom', 'prenom', 'poste', 'cv'
        'numTel' => $request->numTel,
        'adresse' => $request->adresse,
        'role' => $request->role,
        'image' => $imagePath,
        'nom_societe' => $request->nom_societe,
        'active' => false,
        'code_verification' => $verificationCode,
        // Nouveaux champs
        'apropos' => $request->apropos,
        'lien_site_web' => $request->lien_site_web,
        'fax' => $request->fax,
        'domaine_activite' => $request->domaine_activite,
    ]);

    // Envoi de l'email avec le code de vérification
    Mail::to($user->email)->send(new RecruiterAdded($user->nom_societe, $verificationCode));

    // Créer une notification pour les admins
    User::where('role', 'admin')->each(function ($admin) use ($user) {
        Notification::create([
            'type' => 'new_recruiter',
            'message' => "Nouveau recruteur inscrit: {$user->nom_societe}",
            'data' => [
                'recruiter_id' => $user->id,
                'recruiter_name' => $user->nom_societe,
                'recruiter_email' => $user->email,
                'company' => $user->nom_societe,
            ],
            'user_id' => $admin->id,
            'read' => false,
        ]);
    });

    // Génération du token d'authentification
    $token = $user->createToken('backendPFE')->plainTextToken;

    return response()->json([
        'message' => 'Registration successful and email sent!',
        'token' => $token,
        'user' => $user,
    ], 201);
}

/**
 * @OA\Post(
 *     path="/api/verify-code",
 *     summary="Vérification du code de confirmation",
 *     description="Active un compte utilisateur après validation du code de vérification envoyé par email.",
 *     tags={"Authentification"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","verification_code"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *             @OA\Property(property="verification_code", type="string", example="123456")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Compte activé avec succès.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Votre compte a été activé avec succès.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Code de vérification incorrect ou utilisateur non trouvé.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Code de vérification incorrect.")
 *         )
 *     )
 * )
 */

public function verifyCode(Request $request)
{
    $user = User::where('email', $request->email)->first();

    if (!$user || $user->code_verification !== $request->verification_code) {
        return response()->json(['error' => 'Code de vérification incorrect.'], 400);
    }

    // Mise à jour du statut de l'utilisateur pour activer le compte
    $user->active = true;
    $user->code_verification = null; // Supprimer le code après vérification
    $user->save();

    return response()->json(['message' => 'Votre compte a été activé avec succès.'], 200);
}
/**
 * @OA\Post(
 *     path="/api/resend-verification-code",
 *     summary="Renvoyer le code de vérification",
 *     description="Génère et envoie un nouveau code de vérification par email si l'utilisateur n'a pas encore activé son compte.",
 *     tags={"Authentification"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Un nouveau code de vérification a été envoyé.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Un nouveau code de vérification a été envoyé.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Ce compte est déjà vérifié.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Ce compte est déjà vérifié.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Email non trouvé.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Email non trouvé.")
 *         )
 *     )
 * )
 */

public function resendVerificationCodeLogin(Request $request)
{
    // Valider la requête
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email'
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => 'Email non trouvé'], 404);
    }

    // Récupérer l'utilisateur
    $user = User::where('email', $request->email)->first();

    // Vérifier si le compte est déjà actif
    if ($user->active) {
        return response()->json(['error' => 'Ce compte est déjà vérifié'], 400);
    }

    // Générer un nouveau code de vérification
    $newVerificationCode = Str::random(6);

    // Mettre à jour le code de vérification de l'utilisateur
    $user->code_verification = $newVerificationCode;
    $user->save();

    // Renvoyer l'email avec le nouveau code
    Mail::to($user->email)->send(new RecruiterAdded($user->nom_societe, $newVerificationCode));

    return response()->json(['message' => 'Un nouveau code de vérification a été envoyé'], 200);
}



/**
 * @OA\Put(
 *     path="/api/user/updateRec/{id}",
 *     summary="Met à jour les informations de l'utilisateur connecté",
 *     operationId="updateRec",
 *     tags={"Utilisateur"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Les données à mettre à jour pour l'utilisateur.",
 *         @OA\JsonContent(
 *             required={"numTel", "adresse"},
 *             @OA\Property(property="password", type="string", description="Nouveau mot de passe", example="newPassword123"),
 *             @OA\Property(property="numTel", type="string", description="Numéro de téléphone de l'utilisateur", example="123456789"),
 *             @OA\Property(property="adresse", type="string", description="Adresse de l'utilisateur", example="123 Rue Exemple"),
 *             @OA\Property(property="image", type="string", format="binary", description="Nouvelle image de profil"),
 *             @OA\Property(property="apropos", type="string", description="À propos de l'utilisateur", example="Nous sommes une entreprise innovante."),
 *             @OA\Property(property="lien_site_web", type="string", format="url", description="Lien vers le site web de l'utilisateur", example="https://www.exemple.com"),
 *             @OA\Property(property="fax", type="string", description="Numéro de fax de l'utilisateur", example="987654321"),
 *             @OA\Property(property="domaine_activite", type="string", description="Domaine d'activité de l'utilisateur", example="Informatique"),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Utilisateur mis à jour avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Utilisateur mis à jour avec succès."),
 *             @OA\Property(property="user", type="object", 
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="nom_societe", type="string", example="Entreprise X"),
 *                 @OA\Property(property="numTel", type="string", example="123456789"),
 *                 @OA\Property(property="adresse", type="string", example="123 Rue Exemple"),
 *                 @OA\Property(property="image", type="string", example="http://example.com/storage/images/user.jpg"),
 *                 @OA\Property(property="apropos", type="string", example="Nous sommes une entreprise innovante."),
 *                 @OA\Property(property="lien_site_web", type="string", format="url", example="https://www.exemple.com"),
 *                 @OA\Property(property="fax", type="string", example="987654321"),
 *                 @OA\Property(property="domaine_activite", type="string", example="Informatique")
 *             ),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Utilisateur non authentifié.",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Utilisateur non authentifié.")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation des données échouée",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="object", 
 *                 @OA\Property(property="numTel", type="array", @OA\Items(type="string")),
 *                 @OA\Property(property="adresse", type="array", @OA\Items(type="string")),
 *             )
 *         ),
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Une erreur est survenue")
 *         ),
 *     ),
 * )
 */
function updateRec(Request $request)
{
    try {
        // Récupérer l'utilisateur à partir du token
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
        }

        // Validation des données entrantes
        $validator = Validator::make($request->all(), [
            'password' => 'nullable|min:8',
            // Suppression de: 'nom', 'prenom'
            'numTel' => 'nullable|string',
            'adresse' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            // Suppression de: 'cv'
            // Nouveaux champs
            'apropos' => 'nullable|string',
            'lien_site_web' => 'nullable|string|url',
            'fax' => 'nullable|string',
            'domaine_activite' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();


        if (isset($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        // Suppression des mises à jour pour 'nom' et 'prenom'

        if (isset($validatedData['numTel'])) {
            $user->numTel = $validatedData['numTel'];
        }

        if (isset($validatedData['adresse'])) {
            $user->adresse = $validatedData['adresse'];
        }

        // Nouveaux champs
        if (isset($validatedData['apropos'])) {
            $user->apropos = $validatedData['apropos'];
        }

        if (isset($validatedData['lien_site_web'])) {
            $user->lien_site_web = $validatedData['lien_site_web'];
        }

        if (isset($validatedData['fax'])) {
            $user->fax = $validatedData['fax'];
        }

        if (isset($validatedData['domaine_activite'])) {
            $user->domaine_activite = $validatedData['domaine_activite'];
        }

        // Gérer l'upload de l'image
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }
            $imagePath = $request->file('image')->store('images', 'public');
            $user->image = $imagePath;
        }

        // Suppression de la gestion du CV

        // Sauvegarde des modifications
        $user->save();

        // Préparer la réponse
        $userData = [
            'id' => $user->id,
            // Suppression de: 'nom', 'prenom'
            'nom_societe' => $user->nom_societe,
            'numTel' => $user->numTel,
            'adresse' => $user->adresse,
            'image' => $user->image ? asset('storage/' . $user->image) : null,
            // Suppression de: 'cv'
            // Nouveaux champs
            'apropos' => $user->apropos,
            'lien_site_web' => $user->lien_site_web,
            'fax' => $user->fax,
            'domaine_activite' => $user->domaine_activite,
        ];

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès.',
            'user' => $userData
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['error' => 'Une erreur est survenue'], 500);
    }
}


/**
 * @OA\Post(
 *     path="/api/forgot-password",
 *     summary="Envoie un code de vérification par email pour la réinitialisation du mot de passe",
 *     operationId="sendVerificationCode",
 *     tags={"Utilisateur"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="L'email de l'utilisateur pour lequel envoyer le code de vérification.",
 *         @OA\JsonContent(
 *             required={"email"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Code envoyé avec succès.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Code envoyé par email")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Email non trouvé",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Email non trouvé")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Erreur de validation de l'email",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="L'email est requis et doit être valide.")
 *         ),
 *     ),
 * )
 */


// ✅ 1️⃣ Fonction pour envoyer un code aléatoire par email
public function sendVerificationCode(Request $request)
{
    $request->validate(['email' => 'required|email']);

    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['error' => 'Email non trouvé'], 404);
    }

    $code = rand(100000, 999999); // Générer un code à 6 chiffres
    $user->update(['code_verification' => $code]);

    Mail::raw("Votre code de réinitialisation est : $code", function ($message) use ($user) {
        $message->to($user->email)
            ->subject('Réinitialisation de mot de passe');
    });

    return response()->json(['message' => 'Code envoyé par email'], 200);
}


/**
 * @OA\Post(
 *     path="/api/verifyForgetCode",
 *     summary="Vérifie le code de vérification pour réinitialiser le mot de passe",
 *     operationId="verifyCoderest",
 *     tags={"Utilisateur"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="L'email de l'utilisateur et le code de vérification à valider.",
 *         @OA\JsonContent(
 *             required={"email", "code_verification"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *             @OA\Property(property="code_verification", type="string", example="123456")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Code de vérification valide.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Code valide")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Code incorrect ou email invalide",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Code incorrect ou email invalide")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Erreur de validation de l'email ou du code",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="L'email et le code de vérification sont requis.")
 *         ),
 *     ),
 * )
 */


// ✅ 2️⃣ Fonction pour vérifier si le code est valide
public function verifyCoderest(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'code_verification' => 'required',
    ]);

    $user = User::where('email', $request->email)
        ->where('code_verification', $request->code_verification)
        ->first();

    if (!$user) {
        return response()->json(['error' => 'Code incorrect ou email invalide'], 400);
    }

    return response()->json(['message' => 'Code valide'], 200);
}
/**
 * @OA\Post(
 *     path="/api/reset-password",
 *     summary="Réinitialise le mot de passe de l'utilisateur",
 *     operationId="resetPassword",
 *     tags={"Utilisateur"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Email de l'utilisateur et nouveau mot de passe.",
 *         @OA\JsonContent(
 *             required={"email", "new_password"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *             @OA\Property(property="new_password", type="string", format="password", example="new_password_123")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Mot de passe réinitialisé avec succès.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Mot de passe réinitialisé avec succès")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Aucun code de vérification actif ou autre erreur.",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Aucun code de vérification actif")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Email non trouvé.",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Email non trouvé")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Erreur de validation de l'email ou du mot de passe",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="L'email et le mot de passe sont requis.")
 *         ),
 *     ),
 * )
 */

// ✅ 3️⃣ Fonction pour réinitialiser le mot de passe après vérification du code
public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'new_password' => 'required|min:6',
    ]);

    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['error' => 'Email non trouvé'], 404);
    }

    if (!$user->code_verification) {
        return response()->json(['error' => 'Aucun code de vérification actif'], 400);
    }

    $user->update([
        'password' => Hash::make($request->new_password),
        'code_verification' => null, // Réinitialiser le code après utilisation
    ]);

    return response()->json(['message' => 'Mot de passe réinitialisé avec succès'], 200);
}

}