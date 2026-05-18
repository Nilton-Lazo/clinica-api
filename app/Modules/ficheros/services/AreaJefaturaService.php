<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\AreaJefatura;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AreaJefaturaService
{
    use AppliesListingQuery;

    public function __construct(
        private AuditService $audit,
    ) {}

    private function formatCodigo(int $n): string
    {
        $codigo = CodigoCorrelativo::format($n);
        CodigoCorrelativo::guardMaxLength($codigo);

        return $codigo;
    }

    public function peekNextCodigo(): string
    {
        $last = AreaJefatura::query()
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
    private const CACHE_VERSION_KEY = 'ficheros:parametros:caja:area-jefatura:version';

    private function getListCacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 0);
    }

    private function invalidateListCache(): void
    {
        Cache::put(self::CACHE_VERSION_KEY, $this->getListCacheVersion() + 1, 86400);
    }

    public function paginate(GridParams $params): LengthAwarePaginator
    {
        $version = $this->getListCacheVersion();
        $cacheKey = 'ficheros:parametros:caja:area-jefatura:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = AreaJefatura::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'descripcion']);
            $this->applyListingSort($query, $params, ['codigo', 'descripcion', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): AreaJefatura
    {
        return DB::transaction(function () use ($data) {
            $codigo = isset($data['codigo']) && trim((string) $data['codigo']) !== ''
            ? trim((string) $data['codigo'])
            : $this->peekNextCodigo();
            $areaJefatura = AreaJefatura::create([
                'codigo' => $codigo,
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.area-jefatura.create',
                'Crear área o jefatura',
                'caja_area_jefatura',
                (string) $areaJefatura->id,
                [
                    'codigo' => $areaJefatura->codigo,
                    'descripcion' => $areaJefatura->descripcion,
                    'estado' => $areaJefatura->estado,
                ],
                'success',
                201
            );

            $this->invalidateListCache();

            return $areaJefatura;
        });
    }

    public function update(AreaJefatura $areaJefatura, array $data): AreaJefatura
    {
        return DB::transaction(function () use ($areaJefatura, $data) {
            $before = $areaJefatura->only(['codigo', 'descripcion', 'estado']);

            $areaJefatura->fill([
                'codigo' => $data['codigo'],
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);
            $areaJefatura->save();

            $after = $areaJefatura->only(['codigo', 'descripcion', 'estado']);

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.area-jefatura.update',
                'Actualizar área o jefatura',
                'caja_area_jefatura',
                (string) $areaJefatura->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();

            return $areaJefatura;
        });
    }

    public function deactivate(AreaJefatura $areaJefatura): AreaJefatura
    {
        return DB::transaction(function () use ($areaJefatura) {
            $before = $areaJefatura->only(['estado']);
            $areaJefatura->estado = RecordStatus::INACTIVO->value;
            $areaJefatura->save();

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.area-jefatura.deactivate',
                'Desactivar área o jefatura',
                'caja_area_jefatura',
                (string) $areaJefatura->id,
                ['before' => $before, 'after' => $areaJefatura->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();

            return $areaJefatura;
        });
    }
}
