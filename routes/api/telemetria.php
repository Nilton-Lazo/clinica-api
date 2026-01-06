<?php

use Illuminate\Support\Facades\Route;
use App\Modules\telemetria\Controllers\NavigationController;

Route::middleware([
    'auth:sanctum',
    'token.fresh',
    'audit',
    'throttle:auth-actions',
])->group(function () {
    Route::post('/telemetria/navegacion', NavigationController::class);
});
