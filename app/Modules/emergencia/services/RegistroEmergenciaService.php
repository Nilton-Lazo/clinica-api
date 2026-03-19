<?php

namespace App\Modules\emergencia\services;

use App\Core\NroCuentaService;
use App\Modules\admision\models\RegistroEmergencia;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class RegistroEmergenciaService
{
    public function __construct(private NroCuentaService $nroCuentaService) {}

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const CACHE_VERSION_KEY = 'emergencia:registro:version';

    private function getListCacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 0);
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $q = isset($filters['q']) ? trim((string) $filters['q']) : null;
        $fechaDesde = isset($filters['fecha_desde']) ? trim((string) $filters['fecha_desde']) : null;
        $fechaHasta = isset($filters['fecha_hasta']) ? trim((string) $filters['fecha_hasta']) : null;

        $version = $this->getListCacheVersion();
        $cacheKey = sprintf('emergencia:registro:index:%s:%s:%s:%s:%s:%s', $version, $page, $perPage, $q ?? '', $fechaDesde ?? '', $fechaHasta ?? '');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($filters, $perPage, $page) {
            $q = isset($filters['q']) ? trim((string) $filters['q']) : null;
            $fechaDesde = isset($filters['fecha_desde']) ? trim((string) $filters['fecha_desde']) : null;
            $fechaHasta = isset($filters['fecha_hasta']) ? trim((string) $filters['fecha_hasta']) : null;

            $query = RegistroEmergencia::query()
                ->with(['tipoEmergencia:id,codigo,descripcion'])
                ->orderBy('fecha', 'desc')
                ->orderBy('orden', 'asc')
                ->orderBy('id', 'desc');

            if ($fechaDesde !== null && $fechaDesde !== '') {
                $query->whereDate('fecha', '>=', $fechaDesde);
            }
            if ($fechaHasta !== null && $fechaHasta !== '') {
                $query->whereDate('fecha', '<=', $fechaHasta);
            }
            if ($q !== null && $q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('orden', 'ilike', "%{$q}%")
                        ->orWhere('numero_hc', 'ilike', "%{$q}%")
                        ->orWhere('apellidos_nombres', 'ilike', "%{$q}%")
                        ->orWhere('numero_cuenta', 'ilike', "%{$q}%");
                });
            }

            $paginator = $query->paginate($perPage, ['*'], 'page', $page)->appends([
                'per_page' => $perPage,
                'q' => $q,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ]);

            // Transform to include computed patient data
            $paginator->getCollection()->transform(function ($registro) {
                // Find patient to compute age
                $paciente = \App\Modules\admision\models\Paciente::query()
                    ->select(['id', 'fecha_nacimiento', 'sexo'])
                    ->where(function ($q) use ($registro) {
                        $q->where('numero_documento', $registro->numero_hc)
                          ->orWhere('nr', $registro->numero_hc);
                    })
                    ->first();
                
                $edad = $paciente ? $paciente->edad : null;
                $registro->setAttribute('edad_paciente', $edad);
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
        return $record;
    }

    public function update(array $data, int $id): RegistroEmergencia
    {
        $record = RegistroEmergencia::query()->findOrFail($id);
        
        // Allowed fields for update
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
            
            // New fields
            'tipo_emergencia_id' => $data['tipo_emergencia_id'] ?? $record->tipo_emergencia_id,
            'topico_id' => $data['topico_id'] ?? $record->topico_id,
            'medico_emergencia_id' => $data['medico_emergencia_id'] ?? $record->medico_emergencia_id,
            'diagnostico_ingreso' => $data['diagnostico_ingreso'] ?? $record->diagnostico_ingreso,
            
            // SOAT
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
        return str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function nextOrdenForDateInternal(Carbon $fecha): string
    {
        $count = RegistroEmergencia::query()
            ->whereDate('fecha', $fecha->format('Y-m-d'))
            ->count();
        return str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }
}
