<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebRTCController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/call', function() {
    return view('call');
});

// WebRTC signaling routes

Route::get('/webrtc/signal', [WebRTCController::class, 'signal']);
Route::post('/webrtc/signal', [WebRTCController::class, 'signal']);

// Your other routes...
