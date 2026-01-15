<?php

use App\Modules\admision\controllers\ficheros\EspecialidadController;
use App\Modules\admision\controllers\ficheros\ConsultorioController;
use App\Modules\admision\controllers\ficheros\MedicoController;
use App\Modules\admision\controllers\ficheros\TurnoController;
use App\Modules\admision\controllers\citas\ProgramacionMedicaController;

use Illuminate\Support\Facades\Route;

Route::prefix('admision')->middleware(['auth:sanctum', 'token.fresh', 'audit'])->group(function () {
    Route::prefix('ficheros')->group(function () {
        Route::get('especialidades', [EspecialidadController::class, 'index'])->middleware('throttle:api');
        Route::get('especialidades/next-codigo', [EspecialidadController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::post('especialidades', [EspecialidadController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('especialidades/{especialidad}', [EspecialidadController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('especialidades/{especialidad}/desactivar', [EspecialidadController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::get('consultorios', [ConsultorioController::class, 'index'])->middleware('throttle:api');
        Route::post('consultorios', [ConsultorioController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('consultorios/{consultorio}', [ConsultorioController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('consultorios/{consultorio}/desactivar', [ConsultorioController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::get('medicos', [MedicoController::class, 'index'])->middleware('throttle:api');
        Route::get('medicos/next-codigo', [MedicoController::class, 'nextCodigo']);
        Route::post('medicos', [MedicoController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('medicos/{medico}', [MedicoController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('medicos/{medico}/desactivar', [MedicoController::class, 'deactivate'])->middleware('throttle:sensitive-write');   

        Route::get('turnos', [TurnoController::class, 'index'])->middleware('throttle:api');
        Route::post('turnos', [TurnoController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('turnos/{turno}', [TurnoController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('turnos/{turno}/desactivar', [TurnoController::class, 'deactivate'])->middleware('throttle:sensitive-write');
    });

    Route::prefix('citas')->group(function () {
        Route::get('programacion-medica', [ProgramacionMedicaController::class, 'index'])->middleware('throttle:api');
        Route::get('programacion-medica/cupos', [ProgramacionMedicaController::class, 'cupos'])->middleware('throttle:api');
    
        Route::post('programacion-medica', [ProgramacionMedicaController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('programacion-medica/{programacionMedica}', [ProgramacionMedicaController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('programacion-medica/{programacionMedica}/desactivar', [ProgramacionMedicaController::class, 'deactivate'])->middleware('throttle:sensitive-write');
    });
});
