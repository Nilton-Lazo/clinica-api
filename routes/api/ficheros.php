<?php

use App\Modules\ficheros\controllers\EspecialidadController;
use App\Modules\ficheros\controllers\ConsultorioController;
use App\Modules\ficheros\controllers\MedicoController;
use App\Modules\ficheros\controllers\TurnoController;
use App\Modules\ficheros\controllers\TipoIafaController;
use App\Modules\ficheros\controllers\IafaController;
use App\Modules\ficheros\controllers\ContratanteController;
use App\Modules\ficheros\controllers\TarifaController;
use App\Modules\ficheros\controllers\TipoClienteController;
use App\Modules\ficheros\controllers\TarifarioCatalogoController;
use App\Modules\ficheros\controllers\TarifaClonacionController;
use App\Modules\ficheros\controllers\TarifaCategoriaController;
use App\Modules\ficheros\controllers\TarifaSubcategoriaController;
use App\Modules\ficheros\controllers\TarifaServicioController;
use App\Modules\ficheros\controllers\ParametroSistemaController;
use App\Modules\ficheros\controllers\TarifaRecargoNocheController;
use App\Modules\ficheros\controllers\TipoEmergenciaController;
use App\Modules\ficheros\controllers\TopicoController;
use App\Modules\ficheros\controllers\TipoDocumentoController;
use App\Modules\ficheros\controllers\DocumentoAtencionController;

use Illuminate\Support\Facades\Route;

Route::prefix('ficheros')->middleware(['auth:sanctum', 'token.fresh', 'audit'])->group(function () {
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

    Route::get('tarifas/{tarifa}/recargo-noche', [TarifaRecargoNocheController::class, 'index'])->middleware('throttle:api');
    Route::post('tarifas/{tarifa}/recargo-noche', [TarifaRecargoNocheController::class, 'store'])->middleware('throttle:sensitive-write');
    Route::put('tarifas/{tarifa}/recargo-noche/{recargoNoche}', [TarifaRecargoNocheController::class, 'update'])->middleware('throttle:sensitive-write');
    Route::patch('tarifas/{tarifa}/recargo-noche/{recargoNoche}/desactivar', [TarifaRecargoNocheController::class, 'deactivate'])->middleware('throttle:sensitive-write');

    Route::get('parametros/igv', [ParametroSistemaController::class, 'getIgv'])->middleware('throttle:api');
    Route::put('parametros/igv', [ParametroSistemaController::class, 'updateIgv'])->middleware('throttle:sensitive-write');

    Route::get('parametros/emergencia/tipo', [TipoEmergenciaController::class, 'index'])->middleware('throttle:api');
    Route::get('parametros/emergencia/tipo/next-codigo', [TipoEmergenciaController::class, 'nextCodigo'])->middleware('throttle:api');
    Route::post('parametros/emergencia/tipo', [TipoEmergenciaController::class, 'store'])->middleware('throttle:sensitive-write');
    Route::put('parametros/emergencia/tipo/{tipoEmergencia}', [TipoEmergenciaController::class, 'update'])->middleware('throttle:sensitive-write');
    Route::patch('parametros/emergencia/tipo/{tipoEmergencia}/desactivar', [TipoEmergenciaController::class, 'deactivate'])->middleware('throttle:sensitive-write');

    Route::get('parametros/emergencia/topico', [TopicoController::class, 'index'])->middleware('throttle:api');
    Route::get('parametros/emergencia/topico/next-codigo', [TopicoController::class, 'nextCodigo'])->middleware('throttle:api');
    Route::post('parametros/emergencia/topico', [TopicoController::class, 'store'])->middleware('throttle:sensitive-write');
    Route::put('parametros/emergencia/topico/{topico}', [TopicoController::class, 'update'])->middleware('throttle:sensitive-write');
    Route::patch('parametros/emergencia/topico/{topico}/desactivar', [TopicoController::class, 'deactivate'])->middleware('throttle:sensitive-write');

    Route::get('parametros/emergencia/tipo-documento', [TipoDocumentoController::class, 'index'])->middleware('throttle:api');
    Route::get('parametros/emergencia/tipo-documento/next-codigo', [TipoDocumentoController::class, 'nextCodigo'])->middleware('throttle:api');
    Route::post('parametros/emergencia/tipo-documento', [TipoDocumentoController::class, 'store'])->middleware('throttle:sensitive-write');
    Route::put('parametros/emergencia/tipo-documento/{tipoDocumento}', [TipoDocumentoController::class, 'update'])->middleware('throttle:sensitive-write');
    Route::patch('parametros/emergencia/tipo-documento/{tipoDocumento}/desactivar', [TipoDocumentoController::class, 'deactivate'])->middleware('throttle:sensitive-write');

    Route::get('parametros/emergencia/documento-atencion', [DocumentoAtencionController::class, 'index'])->middleware('throttle:api');
    Route::post('parametros/emergencia/documento-atencion', [DocumentoAtencionController::class, 'store'])->middleware('throttle:sensitive-write');
    Route::put('parametros/emergencia/documento-atencion/{documentoAtencion}', [DocumentoAtencionController::class, 'update'])->middleware('throttle:sensitive-write');
    Route::patch('parametros/emergencia/documento-atencion/{documentoAtencion}/desactivar', [DocumentoAtencionController::class, 'deactivate'])->middleware('throttle:sensitive-write');
});
