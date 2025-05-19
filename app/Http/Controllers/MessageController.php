<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use App\Events\NewMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
/**
 * @OA\Components(
 *     @OA\Schema(
 *         schema="Message",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="from_user_id", type="integer", example=1),
 *         @OA\Property(property="to_user_id", type="integer", example=2),
 *         @OA\Property(property="content", type="string", example="Hello!"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-19T00:00:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-19T00:00:00Z")
 *     ),
 *     @OA\Schema(
 *         schema="Error",
 *         type="object",
 *         @OA\Property(property="error", type="string", example="Non authentifié")
 *     )
 * )
 */


class MessageController extends Controller
{

/**
 * @OA\Get(
 *     path="/api/contactable-users",
 *     summary="Récupérer les utilisateurs contactables",
 *     operationId="getContactableUsers",
 *     tags={"Messages"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des utilisateurs contactables",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/User")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non authentifié",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     )
 * )
 */

    public function getContactableUsers()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $user = Auth::user();
        
        try {
            if ($user->role === 'admin') {
                // L'admin peut voir tous les recruteurs
                // Récupérer les recruteurs avec leur dernier message
                $users = User::where('role', 'recruteur')
                    ->get()
                    ->map(function ($user) {
                        // Trouver le dernier message échangé avec cet utilisateur
                        $lastMessage = Message::where(function ($query) use ($user) {
                            $query->where('from_user_id', Auth::id())
                                ->where('to_user_id', $user->id);
                        })->orWhere(function ($query) use ($user) {
                            $query->where('from_user_id', $user->id)
                                ->where('to_user_id', Auth::id());
                        })
                        ->orderBy('created_at', 'desc')
                        ->first();
                        
                        // Ajouter la date du dernier message à l'utilisateur
                        $user->last_message_at = $lastMessage ? $lastMessage->created_at : null;
                        return $user;
                    })
                    ->sortByDesc('last_message_at') // Trier par date du dernier message
                    ->values(); // Réindexer le tableau
            } else {
                // Les recruteurs ne peuvent voir que les admins
                $users = User::where('role', 'admin')->get();
            }
            
            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/messages",
     *     summary="Envoyer un message",
     *     operationId="sendMessage",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"to_user_id", "content"},
     *             @OA\Property(property="to_user_id", type="integer", example=2),
     *             @OA\Property(property="content", type="string", example="Hello!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message envoyé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Message")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function sendMessage(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'content' => 'required|string'
        ]);

        try {
            $message = Message::create([
                'from_user_id' => Auth::id(),
                'to_user_id' => $request->to_user_id,
                'content' => $request->content
            ]);

            // Charger les relations pour la réponse
            $message->load('sender');
            
            // Déclencher l'événement pour la diffusion en temps réel
            event(new NewMessage($message));

            return response()->json($message);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/messages/{userId}",
     *     summary="Obtenir les messages d'un utilisateur",
     *     operationId="getMessages",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des messages",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function getMessages($userId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        try {
            $messages = Message::where(function ($query) use ($userId) {
                $query->where('from_user_id', Auth::id())
                    ->where('to_user_id', $userId);
            })->orWhere(function ($query) use ($userId) {
                $query->where('from_user_id', $userId)
                    ->where('to_user_id', Auth::id());
            })
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

            return response()->json($messages);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Patch(
     *     path="/api/messages/{messageId}/read",
     *     summary="Marquer un message comme lu",
     *     operationId="markAsRead",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="messageId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message marqué comme lu",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function markAsRead($messageId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        try {
            $message = Message::findOrFail($messageId);
            
            if ($message->to_user_id === Auth::id()) {
                $message->update(['read_at' => now()]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
   /**
     * @OA\Patch(
     *     path="/api/messages/read-all/{userId}",
     *     summary="Marquer tous les messages comme lus",
     *     operationId="markAllAsRead",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tous les messages marqués comme lus",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function markAllAsRead($userId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        try {
            Message::where('from_user_id', $userId)
                ->where('to_user_id', Auth::id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/messages/unread-counts",
     *     summary="Obtenir le nombre de messages non lus par utilisateur",
     *     operationId="getUnreadCounts",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Nombre de messages non lus",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="counts", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function getUnreadCounts()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        try {
            $counts = Message::where('to_user_id', Auth::id())
                ->whereNull('read_at')
                ->groupBy('from_user_id')
                ->selectRaw('from_user_id, count(*) as count')
                ->pluck('count', 'from_user_id');

            return response()->json(['counts' => $counts]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/messages/unread-total",
     *     summary="Obtenir le nombre total de messages non lus",
     *     operationId="getUnreadTotal",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Nombre total de messages non lus",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="count", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function getUnreadTotal()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        try {
            $count = Message::where('to_user_id', Auth::id())
                ->whereNull('read_at')
                ->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}