<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{chatroomId}', function ($user, $chatroomId) {
    // For initial testing allow any authenticated user to join chat channels.
    // Adjust authorization logic for production.
    return (bool) $user;
});
