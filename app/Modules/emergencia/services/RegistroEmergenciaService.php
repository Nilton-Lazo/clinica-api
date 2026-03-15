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

            $query = RegistroEmergencia::query()->orderBy('fecha')->orderBy('orden')->orderBy('id');

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

            return $query->paginate($perPage, ['*'], 'page', $page)->appends([
                'per_page' => $perPage,
                'q' => $q,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ]);
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
        ]);
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
