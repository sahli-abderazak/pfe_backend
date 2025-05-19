<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
        // Charger la relation sender pour l'inclure dans l'événement
        $this->message->load('sender');
    }

    public function broadcastOn()
    {
        // Utiliser le même nom de canal que celui utilisé dans le frontend
        return new PresenceChannel('presence-chat.' . $this->message->to_user_id);
    }

    public function broadcastAs()
    {
        return 'new-message';
    }
}