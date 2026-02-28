<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\TipoIafa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TipoIafaService
{
    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        if ($n < 1000) {
            return str_pad((string)$n, 3, '0', STR_PAD_LEFT);
        }

        return (string)$n;
    }

    private function nextCodigoInt(): int
    {
        $last = TipoIafa::query()
            ->select('codigo')
            ->orderByRaw('CAST(codigo AS INTEGER) DESC')
            ->value('codigo');

        $lastInt = $last !== null ? (int)$last : 0;

        return $lastInt + 1;
    }

    public function previewNextCodigo(): string
    {
        return $this->formatCodigo($this->nextCodigoInt());
    }

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const CACHE_VERSION_KEY = 'ficheros:tipos_iafas:version';

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
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int)($filters['page'] ?? 1));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $status = isset($filters['status']) ? trim((string)$filters['status']) : null;

        $version = $this->getListCacheVersion();
        $cacheKey = sprintf('ficheros:tipos_iafas:index:%s:%s:%s:%s:%s', $version, $page, $perPage, $q ?? '', $status ?? '');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($filters, $perPage, $page) {
            $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
            $status = isset($filters['status']) ? trim((string)$filters['status']) : null;

            $query = TipoIafa::query();

            if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
                $query->where('estado', $status);
            }

            if ($q !== null && $q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('codigo', 'ilike', "%{$q}%")
                        ->orWhere('descripcion', 'ilike', "%{$q}%");
                });
            }

            return $query
                ->orderByRaw('CAST(codigo AS INTEGER) ASC')
                ->paginate($perPage, ['*'], 'page', $page)
                ->appends([
                    'per_page' => $perPage,
                    'q' => $q,
                    'status' => $status,
                ]);
        });
    }

    public function create(array $data): TipoIafa
    {
        return DB::transaction(function () use ($data) {
            DB::statement('LOCK TABLE tipos_iafas IN EXCLUSIVE MODE');

            $codigo = $this->formatCodigo($this->nextCodigoInt());

            $tipo = TipoIafa::create([
                'codigo' => $codigo,
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.tipos_iafas.create',
                'Crear tipo de IAFAS',
                'tipo_iafa',
                (string)$tipo->id,
                $tipo->only(['codigo', 'descripcion', 'estado']),
                'success',
                201
            );

            $this->invalidateListCache();
            return $tipo;
        });
    }

    public function update(TipoIafa $tipo, array $data): TipoIafa
    {
        return DB::transaction(function () use ($tipo, $data) {
            $before = $tipo->only(['descripcion', 'estado']);

            $tipo->fill([
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);
            $tipo->save();

            $after = $tipo->only(['descripcion', 'estado']);

            $this->audit->log(
                'masterdata.admision.tipos_iafas.update',
                'Actualizar tipo de IAFAS',
                'tipo_iafa',
                (string)$tipo->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();
            return $tipo;
        });
    }

    public function deactivate(TipoIafa $tipo): TipoIafa
    {
        return DB::transaction(function () use ($tipo) {
            $before = $tipo->only(['estado']);

            $tipo->estado = RecordStatus::INACTIVO->value;
            $tipo->save();

            $this->audit->log(
                'masterdata.admision.tipos_iafas.deactivate',
                'Desactivar tipo de IAFAS',
                'tipo_iafa',
                (string)$tipo->id,
                ['before' => $before, 'after' => $tipo->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();
            return $tipo;
        });
    }
}

