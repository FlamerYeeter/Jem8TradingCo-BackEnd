<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\LiveChat;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{chatroomId}', function ($user, $chatroomId) {
    // Only allow the owner of the chatroom to join its private channel.
    // You can extend this to allow admins or other participants as needed.
    $ownerId = LiveChat::where('chatroom_id', $chatroomId)->value('user_id');
    if (! $ownerId) {
        return false;
    }

    return (int) $ownerId === (int) $user->id;
});
