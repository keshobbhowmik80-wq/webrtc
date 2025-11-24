<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgoraController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/agora/token', [AgoraController::class, 'generateToken']);
Route::get('/call', function () {
    return view('call');
});
