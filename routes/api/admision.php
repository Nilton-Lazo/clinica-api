<?php

use App\Modules\admision\controllers\ficheros\EspecialidadController;
use App\Modules\admision\controllers\ficheros\ConsultorioController;
use App\Modules\admision\controllers\ficheros\MedicoController;
use App\Modules\admision\controllers\ficheros\TurnoController;
use App\Modules\admision\controllers\citas\ProgramacionMedicaController;
use App\Modules\admision\controllers\ficheros\TipoIafaController;
use App\Modules\admision\controllers\ficheros\IafaController;
use App\Modules\admision\controllers\ficheros\ContratanteController;
use App\Modules\admision\controllers\ficheros\TarifaController;
use App\Modules\admision\controllers\ficheros\TipoClienteController;

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
        Route::get('turnos/next-codigo', [TurnoController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::post('turnos', [TurnoController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('turnos/{turno}', [TurnoController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('turnos/{turno}/desactivar', [TurnoController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::get('tipos-iafas', [TipoIafaController::class, 'index'])->middleware('throttle:api');
        Route::get('tipos-iafas/next-codigo', [TipoIafaController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::post('tipos-iafas', [TipoIafaController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('tipos-iafas/{tipoIafa}', [TipoIafaController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('tipos-iafas/{tipoIafa}/desactivar', [TipoIafaController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::get('iafas', [IafaController::class, 'index'])->middleware('throttle:api');
        Route::get('iafas/next-codigo', [IafaController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::post('iafas', [IafaController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('iafas/{iafa}', [IafaController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('iafas/{iafa}/desactivar', [IafaController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::get('contratantes', [ContratanteController::class, 'index'])->middleware('throttle:api');
        Route::get('contratantes/next-codigo', [ContratanteController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::post('contratantes', [ContratanteController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('contratantes/{contratante}', [ContratanteController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('contratantes/{contratante}/desactivar', [ContratanteController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::get('tarifas', [TarifaController::class, 'index'])->middleware('throttle:api');
        Route::get('tarifas/next-codigo', [TarifaController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::post('tarifas', [TarifaController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('tarifas/{tarifa}', [TarifaController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('tarifas/{tarifa}/marcar-base', [TarifaController::class, 'setBase'])->middleware('throttle:sensitive-write');
        Route::patch('tarifas/{tarifa}/desactivar', [TarifaController::class, 'deactivate'])->middleware('throttle:sensitive-write');

        Route::get('tipos-clientes', [TipoClienteController::class, 'index'])->middleware('throttle:api');
        Route::get('tipos-clientes/next-codigo', [TipoClienteController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::post('tipos-clientes', [TipoClienteController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('tipos-clientes/{tipoCliente}', [TipoClienteController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('tipos-clientes/{tipoCliente}/desactivar', [TipoClienteController::class, 'deactivate'])->middleware('throttle:sensitive-write');
    });

    Route::prefix('citas')->group(function () {
        Route::get('programacion-medica', [ProgramacionMedicaController::class, 'index'])->middleware('throttle:api');
        Route::get('programacion-medica/next-codigo', [ProgramacionMedicaController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::get('programacion-medica/cupos', [ProgramacionMedicaController::class, 'cupos'])->middleware('throttle:api');    
        Route::post('programacion-medica', [ProgramacionMedicaController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('programacion-medica/{programacionMedica}', [ProgramacionMedicaController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('programacion-medica/{programacionMedica}/desactivar', [ProgramacionMedicaController::class, 'deactivate'])->middleware('throttle:sensitive-write');
    });
});
