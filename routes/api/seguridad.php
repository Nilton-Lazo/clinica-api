<?php

use Illuminate\Support\Facades\Route;
use App\Modules\seguridad\Controllers\UserController;

Route::middleware([
    'auth:sanctum',
    'token.fresh',
    'audit',
    'throttle:api',
])
    ->prefix('seguridad')
    ->group(function () {
        Route::get('/usuarios', [UserController::class, 'index']);
    });

Route::middleware([
    'auth:sanctum',
    'token.fresh',
    'audit',
    'throttle:sensitive-write',
])
->prefix('seguridad')
->group(function () {
    Route::post('/usuarios', [UserController::class, 'store']);
});