<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\TipoEmergencia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TipoEmergenciaService
{
    public function __construct(
        private AuditService $audit,
    ) {}

    private function formatCodigo(int $n): string
    {
        $codigo = str_pad((string) $n, 3, '0', STR_PAD_LEFT);
        if (strlen($codigo) > 50) {
            throw new \RuntimeException('No se pudo generar el código: excede 50 caracteres.');
        }
        return $codigo;
    }

    public function peekNextCodigo(): string
    {
        $last = TipoEmergencia::query()
            ->select('codigo')
            ->whereRaw("codigo ~ '^[0-9]+$'")
            ->orderByRaw("codigo::int desc")
            ->first();

        $n = 0;
        if ($last && is_string($last->codigo) && $last->codigo !== '') {
            $n = (int) $last->codigo;
        }
        return $this->formatCodigo($n + 1);
    }

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const CACHE_VERSION_KEY = 'ficheros:parametros:emergencia:tipo:version';

    private function getListCacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 0);
    }

    private function invalidateListCache(): void
    {
        Cache::put(self::CACHE_VERSION_KEY, $this->getListCacheVersion() + 1, 86400);
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $q = isset($filters['q']) ? trim((string) $filters['q']) : null;
        $status = isset($filters['status']) ? trim((string) $filters['status']) : null;

        $version = $this->getListCacheVersion();
        $cacheKey = sprintf('ficheros:parametros:emergencia:tipo:index:%s:%s:%s:%s:%s', $version, $page, $perPage, $q ?? '', $status ?? '');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($filters, $perPage, $page) {
            $q = isset($filters['q']) ? trim((string) $filters['q']) : null;
            $status = isset($filters['status']) ? trim((string) $filters['status']) : null;

            $query = TipoEmergencia::query();

            if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
                $query->where('estado', $status);
            }

            if ($q !== null && $q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('codigo', 'ilike', "%{$q}%")
                        ->orWhere('descripcion', 'ilike', "%{$q}%");
                });
            }

            return $query->orderBy('codigo')->paginate($perPage, ['*'], 'page', $page)->appends([
                'per_page' => $perPage,
                'q' => $q,
                'status' => $status,
            ]);
        });
    }

    public function create(array $data): TipoEmergencia
    {
        return DB::transaction(function () use ($data) {
            $codigo = isset($data['codigo']) && trim((string) $data['codigo']) !== ''
            ? trim((string) $data['codigo'])
            : $this->peekNextCodigo();
        $tipoEmergencia = TipoEmergencia::create([
            'codigo' => $codigo,
            'descripcion' => $data['descripcion'],
            'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
        ]);

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.tipo.create',
                'Crear tipo emergencia',
                'tipo_emergencia',
                (string) $tipoEmergencia->id,
                [
                    'codigo' => $tipoEmergencia->codigo,
                    'descripcion' => $tipoEmergencia->descripcion,
                    'estado' => $tipoEmergencia->estado,
                ],
                'success',
                201
            );

            $this->invalidateListCache();

            return $tipoEmergencia;
        });
    }

    public function update(TipoEmergencia $tipoEmergencia, array $data): TipoEmergencia
    {
        return DB::transaction(function () use ($tipoEmergencia, $data) {
            $before = $tipoEmergencia->only(['codigo', 'descripcion', 'estado']);

            $tipoEmergencia->fill([
                'codigo' => $data['codigo'],
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);
            $tipoEmergencia->save();

            $after = $tipoEmergencia->only(['codigo', 'descripcion', 'estado']);

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.tipo.update',
                'Actualizar tipo emergencia',
                'tipo_emergencia',
                (string) $tipoEmergencia->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();

            return $tipoEmergencia;
        });
    }

    public function deactivate(TipoEmergencia $tipoEmergencia): TipoEmergencia
    {
        return DB::transaction(function () use ($tipoEmergencia) {
            $before = $tipoEmergencia->only(['estado']);
            $tipoEmergencia->estado = RecordStatus::INACTIVO->value;
            $tipoEmergencia->save();

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.tipo.deactivate',
                'Desactivar tipo emergencia',
                'tipo_emergencia',
                (string) $tipoEmergencia->id,
                ['before' => $before, 'after' => $tipoEmergencia->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();

            return $tipoEmergencia;
        });
    }
}
