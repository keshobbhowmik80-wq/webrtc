<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Use PUBLIC channel (no authentication required)
Broadcast::channel('webrtc.{roomId}', function () {
    return true;
});
