<?php

use App\Modules\emergencia\controllers\RegistroEmergenciaController;
use Illuminate\Support\Facades\Route;

Route::prefix('emergencia')->middleware(['auth:sanctum', 'token.fresh', 'audit'])->group(function () {
    Route::get('registro/next-orden', [RegistroEmergenciaController::class, 'nextOrden'])->middleware('throttle:api');
    Route::get('registro', [RegistroEmergenciaController::class, 'index'])->middleware('throttle:api');
    Route::post('registro', [RegistroEmergenciaController::class, 'store'])->middleware('throttle:sensitive-write');
});
