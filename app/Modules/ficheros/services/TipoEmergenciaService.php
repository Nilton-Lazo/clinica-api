<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\TipoEmergencia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TipoEmergenciaService
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

    public function paginate(GridParams $params): LengthAwarePaginator
    {
        $version = $this->getListCacheVersion();
        $cacheKey = 'ficheros:parametros:emergencia:tipo:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = TipoEmergencia::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'descripcion']);
            $this->applyListingSort($query, $params, ['codigo', 'descripcion', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
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
