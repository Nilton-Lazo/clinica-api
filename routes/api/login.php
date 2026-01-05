<?php

use Illuminate\Support\Facades\Route;
use App\Modules\login\Controllers\LoginController;

Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:login');

    Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware(['auth:sanctum', 'token.fresh', 'audit', 'throttle:auth-actions']);

Route::get('/me', [LoginController::class, 'me'])
    ->middleware(['auth:sanctum', 'token.fresh']);
    
Route::get('/keep-alive', fn () => response()->json(['ok' => true]))
    ->middleware(['auth:sanctum', 'token.fresh', 'throttle:auth-actions']);