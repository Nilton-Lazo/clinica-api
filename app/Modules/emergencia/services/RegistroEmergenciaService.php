<?php

namespace App\Modules\emergencia\services;

use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\NroCuentaService;
use App\Core\support\CodigoCorrelativo;
use App\Core\realtime\RealtimeBroadcaster;
use App\Core\support\CuentaOrigen;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\RegistroEmergencia;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\services\citas\CuentaSyncService;
use App\Modules\caja\support\EmisionComprobanteFacturacion;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class RegistroEmergenciaService
{
    use AppliesListingQuery;

    public function __construct(
        private NroCuentaService $nroCuentaService,
        private CuentaSyncService $cuentaSyncService,
        private RealtimeBroadcaster $realtime,
    ) {}

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const CACHE_VERSION_KEY = 'emergencia:registro:version';

    private function getListCacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 0);
    }

    public function paginate(GridParams $params): LengthAwarePaginator
    {
        $version = $this->getListCacheVersion();
        $cacheKey = 'emergencia:registro:index:'.$version.':'.$params->toCacheKey('v2');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = RegistroEmergencia::query()
                ->with(['tipoEmergencia:id,codigo,descripcion']);

            $fechaDesde = $params->filter('fecha_desde');
            $fechaHasta = $params->filter('fecha_hasta');
            if (is_string($fechaDesde) && $fechaDesde !== '') {
                $query->whereDate('fecha', '>=', $fechaDesde);
            }
            if (is_string($fechaHasta) && $fechaHasta !== '') {
                $query->whereDate('fecha', '<=', $fechaHasta);
            }

            $this->applyListingSearch($query, $params, ['orden', 'numero_hc', 'apellidos_nombres', 'numero_cuenta']);

            $sort = $params->sort ?? 'orden';
            $allowed = ['orden', 'hora', 'numero_hc', 'numero_cuenta', 'apellidos_nombres', 'sexo', 'topico', 'estado'];
            if (! in_array($sort, $allowed, true)) {
                $sort = 'orden';
            }
            $dir = $params->sortDir === 'desc' ? 'desc' : 'asc';
            if ($sort === 'orden') {
                $query->orderBy('fecha', $dir)->orderBy('orden', $dir);
            } else {
                $query->orderBy($sort, $dir);
            }

            $paginator = $query->orderBy('id', $dir)->paginate($params->perPage, ['*'], 'page', $params->page);

            $paginator->getCollection()->transform(function ($registro) {
                $paciente = \App\Modules\admision\models\Paciente::query()
                    ->select(['id', 'fecha_nacimiento', 'sexo'])
                    ->where(function ($q) use ($registro) {
                        $q->where('numero_documento', $registro->numero_hc)
                          ->orWhere('nr', $registro->numero_hc);
                    })
                    ->first();
                
                $edad = $paciente ? $paciente->edad : null;
                $registro->setAttribute('edad_paciente', $edad);
                if ($this->isCuentaCancelada($registro)) {
                    $registro->setAttribute('estado', 'CANCELADO');
                }
                return $registro;
            });

            return $paginator;
        });
    }

    public function create(array $data): RegistroEmergencia
    {
        $fecha = isset($data['fecha']) ? Carbon::parse($data['fecha']) : now();
        $orden = $this->nextOrdenForDateInternal($fecha);
        $numeroCuenta = $this->nroCuentaService->next();
        $this->ensurePacienteExists((string) $data['numero_hc']);

        $record = RegistroEmergencia::create([
            'orden' => $orden,
            'hora' => $data['hora'] ?? null,
            'numero_hc' => $data['numero_hc'],
            'apellidos_nombres' => $data['apellidos_nombres'],
            'sexo' => $data['sexo'] ?? null,
            'tipo_cliente' => $data['tipo_cliente'] ?? null,
            'fecha' => $fecha->format('Y-m-d'),
            'cuenta' => $data['cuenta'] ?? null,
            'medico_emergencia' => $data['medico_emergencia'] ?? null,
            'medico_especialista' => $data['medico_especialista'] ?? null,
            'topico' => $data['topico'] ?? null,
            'numero_cuenta' => $numeroCuenta,
            'estado' => 'REGISTRADO',
            'tipo_emergencia_id' => $data['tipo_emergencia_id'] ?? null,
            'topico_id' => $data['topico_id'] ?? null,
            'medico_emergencia_id' => $data['medico_emergencia_id'] ?? null,
            'diagnostico_ingreso' => $data['diagnostico_ingreso'] ?? null,
            'soat_activo' => $data['soat_activo'] ?? false,
            'soat_tipo_documento_id' => $data['soat_tipo_documento_id'] ?? null,
            'soat_numero_documento' => $data['soat_numero_documento'] ?? null,
            'soat_titular_referencia' => $data['soat_titular_referencia'] ?? null,
            'soat_poliza' => $data['soat_poliza'] ?? null,
            'soat_placa' => $data['soat_placa'] ?? null,
            'soat_siniestro' => $data['soat_siniestro'] ?? null,
            'soat_tipo_accidente' => $data['soat_tipo_accidente'] ?? null,
            'soat_lugar_accidente' => $data['soat_lugar_accidente'] ?? null,
            'soat_dni_conductor' => $data['soat_dni_conductor'] ?? null,
            'soat_apellido_paterno_conductor' => $data['soat_apellido_paterno_conductor'] ?? null,
            'soat_apellido_materno_conductor' => $data['soat_apellido_materno_conductor'] ?? null,
            'soat_contacto_conductor' => $data['soat_contacto_conductor'] ?? null,
            'soat_fecha_siniestro' => $data['soat_fecha_siniestro'] ?? null,
            'soat_hora_siniestro' => $data['soat_hora_siniestro'] ?? null,
            'soat_datos_intervencion_autoridad' => $data['soat_datos_intervencion_autoridad'] ?? null,
            'soat_documento_atencion_id_1' => $data['soat_documento_atencion_id_1'] ?? null,
            'soat_numero_documento_atencion_1' => $data['soat_numero_documento_atencion_1'] ?? null,
            'soat_documento_atencion_id_2' => $data['soat_documento_atencion_id_2'] ?? null,
            'soat_numero_documento_atencion_2' => $data['soat_numero_documento_atencion_2'] ?? null,
        ]);
        Cache::increment(self::CACHE_VERSION_KEY);
        $this->cuentaSyncService->syncFromRegistroEmergencia($record);
        $this->realtime->entityChanged(
            module: 'emergencia',
            entity: 'registro_emergencia',
            action: 'created',
            id: (int) $record->id,
            scope: (string) $record->numero_cuenta,
            metadata: ['fecha' => $record->fecha?->format('Y-m-d'), 'numero_cuenta' => $record->numero_cuenta],
        );

        return $record;
    }

    public function update(array $data, int $id): RegistroEmergencia
    {
        $record = RegistroEmergencia::query()->findOrFail($id);
        $this->assertCuentaEditable($record);
        $numeroHc = (string) ($data['numero_hc'] ?? $record->numero_hc);
        $this->ensurePacienteExists($numeroHc);

        $record->fill([
            'orden' => $data['orden'] ?? $record->orden,
            'hora' => $data['hora'] ?? $record->hora,
            'numero_hc' => $data['numero_hc'] ?? $record->numero_hc,
            'apellidos_nombres' => $data['apellidos_nombres'] ?? $record->apellidos_nombres,
            'sexo' => $data['sexo'] ?? $record->sexo,
            'tipo_cliente' => $data['tipo_cliente'] ?? $record->tipo_cliente,
            'fecha' => isset($data['fecha']) ? Carbon::parse($data['fecha'])->format('Y-m-d') : $record->fecha,
            'cuenta' => $data['cuenta'] ?? $record->cuenta,
            'medico_emergencia' => $data['medico_emergencia'] ?? $record->medico_emergencia,
            'medico_especialista' => $data['medico_especialista'] ?? $record->medico_especialista,
            'topico' => $data['topico'] ?? $record->topico,

            'tipo_emergencia_id' => $data['tipo_emergencia_id'] ?? $record->tipo_emergencia_id,
            'topico_id' => $data['topico_id'] ?? $record->topico_id,
            'medico_emergencia_id' => $data['medico_emergencia_id'] ?? $record->medico_emergencia_id,
            'diagnostico_ingreso' => $data['diagnostico_ingreso'] ?? $record->diagnostico_ingreso,

            'soat_activo' => $data['soat_activo'] ?? $record->soat_activo,
            'soat_tipo_documento_id' => $data['soat_tipo_documento_id'] ?? $record->soat_tipo_documento_id,
            'soat_numero_documento' => $data['soat_numero_documento'] ?? $record->soat_numero_documento,
            'soat_titular_referencia' => $data['soat_titular_referencia'] ?? $record->soat_titular_referencia,
            'soat_poliza' => $data['soat_poliza'] ?? $record->soat_poliza,
            'soat_placa' => $data['soat_placa'] ?? $record->soat_placa,
            'soat_siniestro' => $data['soat_siniestro'] ?? $record->soat_siniestro,
            'soat_tipo_accidente' => $data['soat_tipo_accidente'] ?? $record->soat_tipo_accidente,
            'soat_lugar_accidente' => $data['soat_lugar_accidente'] ?? $record->soat_lugar_accidente,
            'soat_dni_conductor' => $data['soat_dni_conductor'] ?? $record->soat_dni_conductor,
            'soat_apellido_paterno_conductor' => $data['soat_apellido_paterno_conductor'] ?? $record->soat_apellido_paterno_conductor,
            'soat_apellido_materno_conductor' => $data['soat_apellido_materno_conductor'] ?? $record->soat_apellido_materno_conductor,
            'soat_contacto_conductor' => $data['soat_contacto_conductor'] ?? $record->soat_contacto_conductor,
            'soat_fecha_siniestro' => $data['soat_fecha_siniestro'] ?? $record->soat_fecha_siniestro,
            'soat_hora_siniestro' => $data['soat_hora_siniestro'] ?? $record->soat_hora_siniestro,
            'soat_datos_intervencion_autoridad' => $data['soat_datos_intervencion_autoridad'] ?? $record->soat_datos_intervencion_autoridad,
            'soat_documento_atencion_id_1' => $data['soat_documento_atencion_id_1'] ?? $record->soat_documento_atencion_id_1,
            'soat_numero_documento_atencion_1' => $data['soat_numero_documento_atencion_1'] ?? $record->soat_numero_documento_atencion_1,
            'soat_documento_atencion_id_2' => $data['soat_documento_atencion_id_2'] ?? $record->soat_documento_atencion_id_2,
            'soat_numero_documento_atencion_2' => $data['soat_numero_documento_atencion_2'] ?? $record->soat_numero_documento_atencion_2,
        ]);

        $record->save();
        Cache::increment(self::CACHE_VERSION_KEY);
        $fresh = $record->fresh();
        $this->cuentaSyncService->syncFromRegistroEmergencia($fresh);
        $this->realtime->entityChanged(
            module: 'emergencia',
            entity: 'registro_emergencia',
            action: 'updated',
            id: (int) $record->id,
            scope: (string) $record->numero_cuenta,
            metadata: ['fecha' => $record->fecha?->format('Y-m-d'), 'numero_cuenta' => $record->numero_cuenta],
        );

        return $record;
    }

    public function nextOrdenForDate(?string $fecha = null): string
    {
        $date = $fecha !== null && $fecha !== ''
            ? Carbon::parse($fecha)
            : now();
        $count = RegistroEmergencia::query()
            ->whereDate('fecha', $date->format('Y-m-d'))
            ->count();
        return CodigoCorrelativo::format($count + 1);
    }

    private function nextOrdenForDateInternal(Carbon $fecha): string
    {
        $count = RegistroEmergencia::query()
            ->whereDate('fecha', $fecha->format('Y-m-d'))
            ->count();
        return CodigoCorrelativo::format($count + 1);
    }

    private function ensurePacienteExists(string $numeroHc): void
    {
        $hc = trim($numeroHc);
        if ($hc === '') {
            throw ValidationException::withMessages([
                'numero_hc' => ['Selecciona un paciente antes de guardar el registro de emergencia.'],
            ]);
        }

        $exists = Paciente::query()
            ->where(function ($query) use ($hc) {
                $query->where('numero_documento', $hc)
                    ->orWhere('nr', $hc);
            })
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'numero_hc' => ['No se encontró un paciente activo con la historia clínica seleccionada. Busca y selecciona nuevamente al paciente.'],
            ]);
        }
    }

    private function isCuentaCancelada(RegistroEmergencia $registro): bool
    {
        $cuenta = Cuenta::query()
            ->where('origen', CuentaOrigen::REGISTRO_EMERGENCIA->value)
            ->where('origen_id', (int) $registro->id)
            ->first();

        if ($cuenta && strtoupper(trim((string) ($cuenta->estado ?? ''))) === 'CANCELADO') {
            return true;
        }

        $nro = trim((string) ($cuenta?->nro_cuenta ?? $registro->numero_cuenta ?? ''));
        if ($nro === '') {
            return false;
        }

        return EmisionComprobanteFacturacion::existeFacturadoraParaCuenta($nro);
    }

    private function assertCuentaEditable(RegistroEmergencia $registro): void
    {
        if (! $this->isCuentaCancelada($registro)) {
            return;
        }

        throw ValidationException::withMessages([
            'nro_cuenta' => ['La cuenta de emergencia está cancelada y facturada. No se permiten modificaciones.'],
        ]);
    }
}
