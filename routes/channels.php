<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('presence-chat.{id}', function ($user, $id) {
    return ['id' => $user->id, 'name' => $user->nom . ' ' . $user->prenom];
});