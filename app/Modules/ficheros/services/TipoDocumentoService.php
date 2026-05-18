<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\TipoDocumento;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TipoDocumentoService
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
        $last = TipoDocumento::query()
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
    private const CACHE_VERSION_KEY = 'ficheros:parametros:emergencia:tipo-documento:version';

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
        $cacheKey = 'ficheros:parametros:emergencia:tipo-documento:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = TipoDocumento::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'descripcion']);
            $this->applyListingSort($query, $params, ['codigo', 'descripcion', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): TipoDocumento
    {
        return DB::transaction(function () use ($data) {
            $codigo = isset($data['codigo']) && trim((string) $data['codigo']) !== ''
                ? trim((string) $data['codigo'])
                : $this->peekNextCodigo();
            $tipoDocumento = TipoDocumento::create([
                'codigo' => $codigo,
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.tipo-documento.create',
                'Crear tipo documento',
                'tipo_documento',
                (string) $tipoDocumento->id,
                [
                    'codigo' => $tipoDocumento->codigo,
                    'descripcion' => $tipoDocumento->descripcion,
                    'estado' => $tipoDocumento->estado,
                ],
                'success',
                201
            );

            $this->invalidateListCache();

            return $tipoDocumento;
        });
    }

    public function update(TipoDocumento $tipoDocumento, array $data): TipoDocumento
    {
        return DB::transaction(function () use ($tipoDocumento, $data) {
            $before = $tipoDocumento->only(['codigo', 'descripcion', 'estado']);

            $tipoDocumento->fill([
                'codigo' => $data['codigo'],
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);
            $tipoDocumento->save();

            $after = $tipoDocumento->only(['codigo', 'descripcion', 'estado']);

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.tipo-documento.update',
                'Actualizar tipo documento',
                'tipo_documento',
                (string) $tipoDocumento->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();

            return $tipoDocumento;
        });
    }

    public function deactivate(TipoDocumento $tipoDocumento): TipoDocumento
    {
        return DB::transaction(function () use ($tipoDocumento) {
            $before = $tipoDocumento->only(['estado']);
            $tipoDocumento->estado = RecordStatus::INACTIVO->value;
            $tipoDocumento->save();

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.tipo-documento.deactivate',
                'Desactivar tipo documento',
                'tipo_documento',
                (string) $tipoDocumento->id,
                ['before' => $before, 'after' => $tipoDocumento->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();

            return $tipoDocumento;
        });
    }
}
