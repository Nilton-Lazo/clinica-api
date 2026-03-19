<?php

use App\Modules\emergencia\controllers\RegistroEmergenciaController;
use App\Modules\emergencia\controllers\AtencionEmergenciaController;
use Illuminate\Support\Facades\Route;

Route::prefix('emergencia')->middleware(['auth:sanctum', 'token.fresh', 'audit'])->group(function () {
    Route::get('registro/next-orden', [RegistroEmergenciaController::class, 'nextOrden'])->middleware('throttle:api');
    Route::get('registro', [RegistroEmergenciaController::class, 'index'])->middleware('throttle:api');
    Route::post('registro', [RegistroEmergenciaController::class, 'store'])->middleware('throttle:sensitive-write');
    Route::get('registro/{id}', [RegistroEmergenciaController::class, 'show'])->middleware('throttle:api');
    Route::put('registro/{id}', [RegistroEmergenciaController::class, 'update'])->middleware('throttle:sensitive-write');
    
    Route::get('atencion/{id}', [AtencionEmergenciaController::class, 'show'])->middleware('throttle:api');
    Route::post('atencion/{id}/atencion', [AtencionEmergenciaController::class, 'store'])->middleware('throttle:sensitive-write');
});
