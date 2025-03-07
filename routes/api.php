<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// pour les controlleurs maison
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('houses', HouseController::class);
    Route::get('houses/search', [HouseController::class, 'search']);
});

// pour les controlleurs message
Route::middleware('auth:sanctum')->group(function () {
    Route::get('conversations', [MessageController::class, 'getConversations']);
    Route::get('conversations/{conversation}/messages', [MessageController::class, 'getMessages']);
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'sendMessage']);
    Route::post('conversations', [MessageController::class, 'startConversation']);
});