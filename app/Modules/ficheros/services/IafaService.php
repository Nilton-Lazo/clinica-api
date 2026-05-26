<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\Iafa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IafaService
{
    use AppliesListingQuery;

    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        return CodigoCorrelativo::format($n);
    }

    private function nextCodigoInt(): int
    {
        $last = Iafa::query()
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
    private const CACHE_VERSION_KEY = 'ficheros:iafas:version';

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
        $cacheKey = 'ficheros:iafas:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = Iafa::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'razon_social', 'descripcion_corta', 'ruc']);
            $this->applyListingSort($query, $params, ['codigo', 'razon_social', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): Iafa
    {
        return DB::transaction(function () use ($data) {
            DB::statement('LOCK TABLE iafas IN EXCLUSIVE MODE');

            $codigo = $this->formatCodigo($this->nextCodigoInt());

            $iafa = Iafa::create([
                'codigo' => $codigo,
                'tipo_iafa_id' => $data['tipo_iafa_id'],
                'razon_social' => $data['razon_social'],
                'descripcion_corta' => $data['descripcion_corta'],
                'ruc' => $data['ruc'],
                'direccion' => $data['direccion'] ?? null,
                'representante_legal' => $data['representante_legal'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'pagina_web' => $data['pagina_web'] ?? null,
                'fecha_inicio_cobertura' => $data['fecha_inicio_cobertura'],
                'fecha_fin_cobertura' => $data['fecha_fin_cobertura'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.iafas.create',
                'Crear IAFAS',
                'iafa',
                (string)$iafa->id,
                $iafa->only([
                    'codigo',
                    'tipo_iafa_id',
                    'razon_social',
                    'descripcion_corta',
                    'ruc',
                    'direccion',
                    'representante_legal',
                    'telefono',
                    'pagina_web',
                    'fecha_inicio_cobertura',
                    'fecha_fin_cobertura',
                    'estado',
                ]),
                'success',
                201
            );

            $this->invalidateListCache();
            return $iafa;
        });
    }

    public function update(Iafa $iafa, array $data): Iafa
    {
        return DB::transaction(function () use ($iafa, $data) {
            $before = $iafa->only([
                'tipo_iafa_id',
                'razon_social',
                'descripcion_corta',
                'ruc',
                'direccion',
                'representante_legal',
                'telefono',
                'pagina_web',
                'fecha_inicio_cobertura',
                'fecha_fin_cobertura',
                'estado',
            ]);

            $iafa->fill([
                'tipo_iafa_id' => $data['tipo_iafa_id'],
                'razon_social' => $data['razon_social'],
                'descripcion_corta' => $data['descripcion_corta'],
                'ruc' => $data['ruc'],
                'direccion' => $data['direccion'] ?? null,
                'representante_legal' => $data['representante_legal'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'pagina_web' => $data['pagina_web'] ?? null,
                'fecha_inicio_cobertura' => $data['fecha_inicio_cobertura'],
                'fecha_fin_cobertura' => $data['fecha_fin_cobertura'],
                'estado' => $data['estado'],
            ]);
            $iafa->save();

            $after = $iafa->only([
                'tipo_iafa_id',
                'razon_social',
                'descripcion_corta',
                'ruc',
                'direccion',
                'representante_legal',
                'telefono',
                'pagina_web',
                'fecha_inicio_cobertura',
                'fecha_fin_cobertura',
                'estado',
            ]);

            $this->audit->log(
                'masterdata.admision.iafas.update',
                'Actualizar IAFAS',
                'iafa',
                (string)$iafa->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();
            return $iafa;
        });
    }

    public function deactivate(Iafa $iafa): Iafa
    {
        return DB::transaction(function () use ($iafa) {
            $before = $iafa->only(['estado']);

            $iafa->estado = RecordStatus::INACTIVO->value;
            $iafa->save();

            $this->audit->log(
                'masterdata.admision.iafas.deactivate',
                'Desactivar IAFAS',
                'iafa',
                (string)$iafa->id,
                ['before' => $before, 'after' => $iafa->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();
            return $iafa;
        });
    }
}

