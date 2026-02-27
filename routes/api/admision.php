<?php

use App\Modules\admision\controllers\citas\ProgramacionMedicaController;
use App\Modules\admision\controllers\citas\AgendaMedicaController;
use App\Modules\admision\controllers\citas\CitaAtencionController;
use App\Modules\admision\controllers\catalogos\CatalogoPacienteController;
use App\Modules\admision\controllers\pacientes\PacienteController;

use Illuminate\Support\Facades\Route;

Route::prefix('admision')->middleware(['auth:sanctum', 'token.fresh', 'audit'])->group(function () {
    Route::prefix('citas')->group(function () {
        Route::get('programacion-medica', [ProgramacionMedicaController::class, 'index'])->middleware('throttle:api');
        Route::get('programacion-medica/next-codigo', [ProgramacionMedicaController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::get('programacion-medica/cupos', [ProgramacionMedicaController::class, 'cupos'])->middleware('throttle:api');    
        Route::post('programacion-medica', [ProgramacionMedicaController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('programacion-medica/{programacionMedica}', [ProgramacionMedicaController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('programacion-medica/{programacionMedica}/desactivar', [ProgramacionMedicaController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::get('agenda-medica/init', [AgendaMedicaController::class, 'init'])->middleware('throttle:agenda-api');
        Route::get('agenda-medica/opciones', [AgendaMedicaController::class, 'opciones'])->middleware('throttle:agenda-api');
        Route::get('agenda-medica/slots', [AgendaMedicaController::class, 'slots'])->middleware('throttle:agenda-api');
        Route::get('agenda-medica', [AgendaMedicaController::class, 'index'])->middleware('throttle:agenda-api');
        Route::post('agenda-medica', [AgendaMedicaController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::patch('agenda-medica/{id}/anular', [AgendaMedicaController::class, 'anular'])->middleware('throttle:sensitive-write');
        Route::get('agenda-medica/{id}/atencion', [CitaAtencionController::class, 'show'])->middleware('throttle:agenda-api');
        Route::post('agenda-medica/{id}/atencion', [CitaAtencionController::class, 'store'])->middleware('throttle:sensitive-write');
    });

    Route::prefix('catalogos')->group(function () {
        Route::get('paciente-form', [CatalogoPacienteController::class, 'pacienteForm'])->middleware('throttle:api');
        Route::get('paises', [CatalogoPacienteController::class, 'paises'])->middleware('throttle:api');
        Route::get('paises/list', [CatalogoPacienteController::class, 'paisesList'])->middleware('throttle:api');
        Route::get('ubigeos', [CatalogoPacienteController::class, 'ubigeos'])->middleware('throttle:api');
    });

    Route::prefix('pacientes')->group(function () {
        Route::get('', [PacienteController::class, 'index'])->middleware('throttle:api');
        Route::get('{paciente}', [PacienteController::class, 'show'])->middleware('throttle:api');
    
        Route::post('', [PacienteController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('{paciente}', [PacienteController::class, 'update'])->middleware('throttle:sensitive-write');

        Route::patch('{paciente}/desactivar', [PacienteController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::post('{paciente}/planes', [PacienteController::class, 'addPlan'])->middleware('throttle:sensitive-write');
        Route::put('{paciente}/planes/{plan}', [PacienteController::class, 'updatePlan'])->middleware('throttle:sensitive-write');
        Route::patch('planes/{plan}/desactivar', [PacienteController::class, 'deactivatePlan'])->middleware('throttle:sensitive-write');
    });
});
