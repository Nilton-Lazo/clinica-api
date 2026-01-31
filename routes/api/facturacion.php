<?php

use App\Modules\admision\controllers\ficheros\TarifaController;
use App\Modules\admision\controllers\ficheros\TarifaCategoriaController;
use App\Modules\admision\controllers\ficheros\TarifaClonacionController;
use App\Modules\admision\controllers\ficheros\TarifaServicioController;
use App\Modules\admision\controllers\ficheros\TarifaSubcategoriaController;
use App\Modules\admision\controllers\ficheros\TarifarioCatalogoController;
use Illuminate\Support\Facades\Route;

Route::prefix('facturacion')->middleware(['auth:sanctum', 'token.fresh', 'audit'])->group(function () {
    Route::prefix('tarifario')->group(function () {
        Route::get('tarifas', [TarifaController::class, 'index'])->middleware('throttle:api');
        Route::get('tarifas/next-codigo', [TarifaController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::post('tarifas', [TarifaController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('tarifas/{tarifa}', [TarifaController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('tarifas/{tarifa}/marcar-base', [TarifaController::class, 'setBase'])->middleware('throttle:sensitive-write');
        Route::patch('tarifas/{tarifa}/desactivar', [TarifaController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::get('tarifas/operativas', [TarifarioCatalogoController::class, 'tarifasOperativas'])->middleware('throttle:api');
        Route::get('tarifas/base', [TarifarioCatalogoController::class, 'tarifaBase'])->middleware('throttle:api');
        Route::get('tarifas/base/arbol', [TarifarioCatalogoController::class, 'arbolBase'])->middleware('throttle:api');
        Route::get('tarifas/{tarifa}/servicios', [TarifarioCatalogoController::class, 'servicios'])->middleware('throttle:api');

        Route::post('tarifas/{tarifa}/clonar-desde-base', [TarifaClonacionController::class, 'cloneFromBase'])->middleware('throttle:sensitive-write');

        Route::get('tarifas/{tarifa}/categorias', [TarifaCategoriaController::class, 'index'])->middleware('throttle:api');
        Route::get('tarifas/{tarifa}/categorias/lookup', [TarifaCategoriaController::class, 'lookup'])->middleware('throttle:api');
        Route::get('tarifas/{tarifa}/categorias/next-codigo', [TarifaCategoriaController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::post('tarifas/{tarifa}/categorias', [TarifaCategoriaController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('tarifas/{tarifa}/categorias/{categoria}', [TarifaCategoriaController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('tarifas/{tarifa}/categorias/{categoria}/desactivar', [TarifaCategoriaController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::get('tarifas/{tarifa}/subcategorias', [TarifaSubcategoriaController::class, 'index'])->middleware('throttle:api');
        Route::get('tarifas/{tarifa}/subcategorias/lookup', [TarifaSubcategoriaController::class, 'lookup'])->middleware('throttle:api');
        Route::get('tarifas/{tarifa}/subcategorias/next-codigo', [TarifaSubcategoriaController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::post('tarifas/{tarifa}/subcategorias', [TarifaSubcategoriaController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('tarifas/{tarifa}/subcategorias/{subcategoria}', [TarifaSubcategoriaController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('tarifas/{tarifa}/subcategorias/{subcategoria}/desactivar', [TarifaSubcategoriaController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::get('tarifas/{tarifa}/servicios-crud', [TarifaServicioController::class, 'index'])->middleware('throttle:api');
        Route::get('tarifas/{tarifa}/servicios-crud/next-codigo', [TarifaServicioController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::post('tarifas/{tarifa}/servicios-crud', [TarifaServicioController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('tarifas/{tarifa}/servicios-crud/{servicio}', [TarifaServicioController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('tarifas/{tarifa}/servicios-crud/{servicio}/desactivar', [TarifaServicioController::class, 'deactivate'])->middleware('throttle:sensitive-write');
    });
});
