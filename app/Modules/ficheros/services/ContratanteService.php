<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\Contratante;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ContratanteService
{
    use AppliesListingQuery;

    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        return CodigoCorrelativo::format($n);
    }

    private function nextCodigoInt(): int
    {
        $last = Contratante::query()
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
    private const CACHE_VERSION_KEY = 'ficheros:contratantes:version';

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
        $cacheKey = 'ficheros:contratantes:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = Contratante::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'razon_social', 'ruc', 'telefono']);
            $this->applyListingSort($query, $params, ['codigo', 'razon_social', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): Contratante
    {
        return DB::transaction(function () use ($data) {
            DB::statement('LOCK TABLE contratantes IN EXCLUSIVE MODE');

            $codigo = $this->formatCodigo($this->nextCodigoInt());

            $contratante = Contratante::create([
                'codigo' => $codigo,
                'razon_social' => $data['razon_social'],
                'ruc' => $data['ruc'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.contratantes.create',
                'Crear contratante',
                'contratante',
                (string)$contratante->id,
                $contratante->only(['codigo', 'razon_social', 'ruc', 'telefono', 'direccion', 'estado']),
                'success',
                201
            );

            $this->invalidateListCache();
            return $contratante;
        });
    }

    public function update(Contratante $contratante, array $data): Contratante
    {
        return DB::transaction(function () use ($contratante, $data) {
            $before = $contratante->only(['razon_social', 'ruc', 'telefono', 'direccion', 'estado']);

            $contratante->fill([
                'razon_social' => $data['razon_social'],
                'ruc' => $data['ruc'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'estado' => $data['estado'],
            ]);
            $contratante->save();

            $after = $contratante->only(['razon_social', 'ruc', 'telefono', 'direccion', 'estado']);

            $this->audit->log(
                'masterdata.admision.contratantes.update',
                'Actualizar contratante',
                'contratante',
                (string)$contratante->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();
            return $contratante;
        });
    }

    public function deactivate(Contratante $contratante): Contratante
    {
        return DB::transaction(function () use ($contratante) {
            $before = $contratante->only(['estado']);

            $contratante->estado = RecordStatus::INACTIVO->value;
            $contratante->save();

            $this->audit->log(
                'masterdata.admision.contratantes.deactivate',
                'Desactivar contratante',
                'contratante',
                (string)$contratante->id,
                ['before' => $before, 'after' => $contratante->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();
            return $contratante;
        });
    }
}

