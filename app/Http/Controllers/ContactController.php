<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class ContactController extends Controller
{
        /**
     * @OA\Post(
     *     path="/api/contacts",
     *     summary="Envoyer un message de contact",
     *     operationId="storeContact",
     *     tags={"Contact"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom", "email", "sujet", "message"},
     *             @OA\Property(property="nom", type="string", example="Jean Dupont"),
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
     *             @OA\Property(property="sujet", type="string", example="Problème technique"),
     *             @OA\Property(property="message", type="string", example="J'ai un problème avec mon compte.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message envoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Message envoyé avec succès !"),
     *             @OA\Property(property="contact", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nom", type="string", example="Jean Dupont"),
     *                 @OA\Property(property="email", type="string", example="jean.dupont@example.com"),
     *                 @OA\Property(property="sujet", type="string", example="Problème technique"),
     *                 @OA\Property(property="message", type="string", example="J'ai un problème avec mon compte.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Validation des données
        $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'sujet' => 'required|string|max:255',
            'message' => 'required|string',
        ]);
    
        // Création du contact dans la base de données
        $contact = Contact::create([
            'nom' => $request->nom,
            'email' => $request->email,
            'sujet' => $request->sujet,
            'message' => $request->message,
        ]);
    
        // Créer une notification pour les admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'type' => 'new_contact',
                'message' => "Nouveau message de contact: {$request->sujet}",
                'data' => [
                    'contact_id' => $contact->id,
                    'name' => $contact->nom,
                    'email' => $contact->email,
                    'subject' => $contact->sujet,
                    'message_preview' => substr($contact->message, 0, 100) . (strlen($contact->message) > 100 ? '...' : ''),
                ],
                'user_id' => $admin->id,
                'read' => false,
            ]);
        }
    
        // Retourner une réponse JSON (ou redirection si utilisé en web)
        return response()->json([
            'message' => 'Message envoyé avec succès !',
            'contact' => $contact
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/showcontacts",
     *     summary="Récupérer tous les messages de contact",
     *     operationId="getContacts",
     *     tags={"Contact"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des messages de contact",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Contact")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $contacts = Contact::all(); // Récupérer tous les contacts
        return response()->json($contacts); // Retourner les données en JSON
    }
    /**
     * @OA\Delete(
     *     path="/api/deleteContact/{id}",
     *     summary="Supprimer un message de contact",
     *     operationId="deleteContact",
     *     tags={"Contact"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Contact supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contact non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Contact non trouvé")
     *         )
     *     )
     * )
     */
    public function deleteContact($id)
    {
        $contact = Contact::find($id);
    
        if (!$contact) {
            return response()->json(['message' => 'Contact non trouvé'], 404);
        }
    
        $contact->delete();
    
        return response()->json(['message' => 'Contact supprimé avec succès']);
    }

        /**
     * @OA\Patch(
     *     path="/api/markasreplied/{id}",
     *     summary="Marquer un message comme répondu",
     *     operationId="markAsReplied",
     *     tags={"Contact"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact marqué comme répondu",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Contact marqué comme répondu"),
     *             @OA\Property(property="contact", type="object",
     *                 @OA\Property(property="repondu", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contact non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Contact non trouvé")
     *         )
     *     )
     * )
     */
    public function markAsReplied($id)
    {
        $contact = Contact::find($id);
        
        if (!$contact) {
            return response()->json(['message' => 'Contact non trouvé'], 404);
        }
        
        $contact->repondu = true;
        $contact->save();
        
        return response()->json(['message' => 'Contact marqué comme répondu', 'contact' => $contact]);
    }
    
}