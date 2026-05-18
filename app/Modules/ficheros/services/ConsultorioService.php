<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Consultorio;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ConsultorioService
{
    use AppliesListingQuery;

    public function __construct(private AuditService $audit) {}

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const CACHE_VERSION_KEY = 'ficheros:consultorios:version';

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
        $cacheKey = 'ficheros:consultorios:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = Consultorio::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['abreviatura', 'descripcion']);
            $this->applyListingSort($query, $params, ['abreviatura', 'descripcion', 'estado'], 'abreviatura');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): Consultorio
    {
        return DB::transaction(function () use ($data) {
            $consultorio = Consultorio::create([
                'abreviatura' => $data['abreviatura'],
                'descripcion' => $data['descripcion'],
                'es_tercero' => (bool)($data['es_tercero'] ?? false),
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.consultorios.create',
                'Crear consultorio',
                'consultorio',
                (string)$consultorio->id,
                [
                    'abreviatura' => $consultorio->abreviatura,
                    'descripcion' => $consultorio->descripcion,
                    'es_tercero' => $consultorio->es_tercero,
                    'estado' => $consultorio->estado,
                ],
                'success',
                201
            );

            $this->invalidateListCache();
            return $consultorio;
        });
    }

    public function update(Consultorio $consultorio, array $data): Consultorio
    {
        return DB::transaction(function () use ($consultorio, $data) {
            $before = $consultorio->only(['abreviatura', 'descripcion', 'es_tercero', 'estado']);

            $consultorio->fill([
                'abreviatura' => $data['abreviatura'],
                'descripcion' => $data['descripcion'],
                'es_tercero' => (bool)$data['es_tercero'],
                'estado' => $data['estado'],
            ]);

            $consultorio->save();

            $after = $consultorio->only(['abreviatura', 'descripcion', 'es_tercero', 'estado']);

            $this->audit->log(
                'masterdata.admision.consultorios.update',
                'Actualizar consultorio',
                'consultorio',
                (string)$consultorio->id,
                [
                    'before' => $before,
                    'after' => $after,
                ],
                'success',
                200
            );

            $this->invalidateListCache();
            return $consultorio;
        });
    }

    public function deactivate(Consultorio $consultorio): Consultorio
    {
        return DB::transaction(function () use ($consultorio) {
            $before = $consultorio->only(['estado']);

            $consultorio->estado = RecordStatus::INACTIVO->value;
            $consultorio->save();

            $this->audit->log(
                'masterdata.admision.consultorios.deactivate',
                'Desactivar consultorio',
                'consultorio',
                (string)$consultorio->id,
                [
                    'before' => $before,
                    'after' => $consultorio->only(['estado']),
                ],
                'success',
                200
            );

            $this->invalidateListCache();
            return $consultorio;
        });
    }
}

