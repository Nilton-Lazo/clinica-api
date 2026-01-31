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
use App\Modules\admision\controllers\catalogos\CatalogoPacienteController;
use App\Modules\admision\controllers\pacientes\PacienteController;
use App\Modules\admision\controllers\ficheros\TarifarioCatalogoController;
use App\Modules\admision\controllers\ficheros\TarifaClonacionController;
use App\Modules\admision\controllers\ficheros\TarifaCategoriaController;
use App\Modules\admision\controllers\ficheros\TarifaSubcategoriaController;
use App\Modules\admision\controllers\ficheros\TarifaServicioController;

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

    Route::prefix('citas')->group(function () {
        Route::get('programacion-medica', [ProgramacionMedicaController::class, 'index'])->middleware('throttle:api');
        Route::get('programacion-medica/next-codigo', [ProgramacionMedicaController::class, 'nextCodigo'])->middleware('throttle:api');
        Route::get('programacion-medica/cupos', [ProgramacionMedicaController::class, 'cupos'])->middleware('throttle:api');    
        Route::post('programacion-medica', [ProgramacionMedicaController::class, 'store'])->middleware('throttle:sensitive-write');
        Route::put('programacion-medica/{programacionMedica}', [ProgramacionMedicaController::class, 'update'])->middleware('throttle:sensitive-write');
        Route::patch('programacion-medica/{programacionMedica}/desactivar', [ProgramacionMedicaController::class, 'deactivate'])->middleware('throttle:sensitive-write');
    });

    Route::prefix('catalogos')->group(function () {
        Route::get('paciente-form', [CatalogoPacienteController::class, 'pacienteForm'])->middleware('throttle:api');
        Route::get('paises', [CatalogoPacienteController::class, 'paises'])->middleware('throttle:api');
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
