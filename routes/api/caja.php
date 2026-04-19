<?php

use App\Modules\caja\controllers\CajaAperturaController;
use App\Modules\caja\controllers\ComprobanteEmisionBootstrapController;
use App\Modules\caja\controllers\ComprobanteEmisionCatalogController;
use App\Modules\caja\controllers\ComprobanteEmisionRegistrarController;
use App\Modules\caja\controllers\ReporteIngresosCajaController;
use Illuminate\Support\Facades\Route;

Route::prefix('caja')->middleware(['auth:sanctum', 'token.fresh', 'audit'])->group(function () {
    Route::get('emision-comprobantes/bootstrap', [ComprobanteEmisionBootstrapController::class, 'bootstrap'])->middleware('throttle:api');
    Route::get('emision-comprobantes/catalogo', [ComprobanteEmisionCatalogController::class, 'catalogo'])->middleware('throttle:api');
    Route::post('emision-comprobantes/registrar', [ComprobanteEmisionRegistrarController::class, 'store'])->middleware('throttle:sensitive-write');
    Route::get('reporte-ingresos/bootstrap', [ReporteIngresosCajaController::class, 'bootstrap'])->middleware('throttle:api');
    Route::get('reporte-ingresos/movimientos', [ReporteIngresosCajaController::class, 'movimientos'])->middleware('throttle:api');
    Route::get('aperturas/next-codigo', [CajaAperturaController::class, 'nextCodigo'])->middleware('throttle:api');
    Route::get('aperturas/resumen', [CajaAperturaController::class, 'resumen'])->middleware('throttle:api');
    Route::post('aperturas', [CajaAperturaController::class, 'store'])->middleware('throttle:sensitive-write');
    Route::post('aperturas/cerrar', [CajaAperturaController::class, 'close'])->middleware('throttle:sensitive-write');
});
