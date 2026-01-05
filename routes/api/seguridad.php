<?php

use Illuminate\Support\Facades\Route;
use App\Modules\seguridad\Controllers\UserController;

Route::middleware([
    'auth:sanctum',
    'token.fresh',
    'audit',
    'throttle:sensitive-write'
])
->prefix('seguridad')
->group(function () {
    Route::post('/usuarios', [UserController::class, 'store']);
});