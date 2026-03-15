<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\DocumentoAtencion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DocumentoAtencionService
{
    public function __construct(
        private AuditService $audit,
    ) {}

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

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $q = isset($filters['q']) ? trim((string) $filters['q']) : null;
        $status = isset($filters['status']) ? trim((string) $filters['status']) : null;

        $version = $this->getListCacheVersion();
        $cacheKey = sprintf('ficheros:parametros:emergencia:documento-atencion:index:%s:%s:%s:%s:%s', $version, $page, $perPage, $q ?? '', $status ?? '');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($filters, $perPage, $page) {
            $q = isset($filters['q']) ? trim((string) $filters['q']) : null;
            $status = isset($filters['status']) ? trim((string) $filters['status']) : null;

            $query = DocumentoAtencion::query();

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

    public function create(array $data): DocumentoAtencion
    {
        return DB::transaction(function () use ($data) {
            $codigo = trim((string) ($data['codigo'] ?? ''));
            if ($codigo === '') {
                throw new \InvalidArgumentException('El código es obligatorio.');
            }
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
