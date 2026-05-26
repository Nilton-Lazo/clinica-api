<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\DocumentoAtencion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DocumentoAtencionService
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
        $last = DocumentoAtencion::query()
            ->select('codigo')
            ->whereRaw("codigo ~ '^[0-9]+$'")
            ->orderByRaw('codigo::int desc')
            ->value('codigo');

        return CodigoCorrelativo::nextFromLast($last);
    }

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const CACHE_VERSION_KEY = 'ficheros:parametros:emergencia:documento-atencion:version';

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
        $cacheKey = 'ficheros:parametros:emergencia:documento-atencion:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = DocumentoAtencion::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'descripcion']);
            $this->applyListingSort($query, $params, ['codigo', 'descripcion', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): DocumentoAtencion
    {
        return DB::transaction(function () use ($data) {
            $codigo = isset($data['codigo']) && trim((string) $data['codigo']) !== ''
                ? trim((string) $data['codigo'])
                : $this->peekNextCodigo();
            $documentoAtencion = DocumentoAtencion::create([
                'codigo' => $codigo,
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.documento-atencion.create',
                'Crear documento atención',
                'documento_atencion',
                (string) $documentoAtencion->id,
                [
                    'codigo' => $documentoAtencion->codigo,
                    'descripcion' => $documentoAtencion->descripcion,
                    'estado' => $documentoAtencion->estado,
                ],
                'success',
                201
            );

            $this->invalidateListCache();

            return $documentoAtencion;
        });
    }

    public function update(DocumentoAtencion $documentoAtencion, array $data): DocumentoAtencion
    {
        return DB::transaction(function () use ($documentoAtencion, $data) {
            $before = $documentoAtencion->only(['codigo', 'descripcion', 'estado']);

            $documentoAtencion->fill([
                'codigo' => $data['codigo'],
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);
            $documentoAtencion->save();

            $after = $documentoAtencion->only(['codigo', 'descripcion', 'estado']);

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.documento-atencion.update',
                'Actualizar documento atención',
                'documento_atencion',
                (string) $documentoAtencion->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();

            return $documentoAtencion;
        });
    }

    public function deactivate(DocumentoAtencion $documentoAtencion): DocumentoAtencion
    {
        return DB::transaction(function () use ($documentoAtencion) {
            $before = $documentoAtencion->only(['estado']);
            $documentoAtencion->estado = RecordStatus::INACTIVO->value;
            $documentoAtencion->save();

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.documento-atencion.deactivate',
                'Desactivar documento atención',
                'documento_atencion',
                (string) $documentoAtencion->id,
                ['before' => $before, 'after' => $documentoAtencion->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();

            return $documentoAtencion;
        });
    }
}
