<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
     /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Obtenir les notifications de l'utilisateur",
     *     operationId="getNotifications",
     *     tags={"Notifications"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des notifications de l'utilisateur",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="notifications", type="array", @OA\Items(ref="#/components/schemas/Notification")),
     *             @OA\Property(property="unread_count", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Non autorisé")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $user = auth()->user();
        
        return response()->json([
            'notifications' => $user->notifications()->orderBy('created_at', 'desc')->get(),
            'unread_count' => $user->notifications()->where('read', false)->count()
        ]);
    }
      /**
     * @OA\Patch(
     *     path="/api/notifications/{notification}",
     *     summary="Marquer une notification comme lue",
     *     operationId="markNotificationAsRead",
     *     tags={"Notifications"},
     *     @OA\Parameter(
     *         name="notification",
     *         in="path",
     *         required=true,
     *         description="ID de la notification",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marquée comme lue",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification marked as read")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification non trouvée")
     *         )
     *     )
     * )
     */  
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->update(['read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }
        /**
     * @OA\Patch(
     *     path="/api/notifications",
     *     summary="Marquer toutes les notifications comme lues",
     *     operationId="markAllNotificationsAsRead",
     *     tags={"Notifications"},
     *     @OA\Response(
     *         response=200,
     *         description="Toutes les notifications marquées comme lues",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="All notifications marked as read")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Non autorisé")
     *         )
     *     )
     * )
     */
    public function markAllAsRead()
    {
        auth()->user()->notifications()->where('read', false)->update(['read' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
