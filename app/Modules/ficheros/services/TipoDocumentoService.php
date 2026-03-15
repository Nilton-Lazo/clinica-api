<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\TipoDocumento;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TipoDocumentoService
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

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $q = isset($filters['q']) ? trim((string) $filters['q']) : null;
        $status = isset($filters['status']) ? trim((string) $filters['status']) : null;

        $version = $this->getListCacheVersion();
        $cacheKey = sprintf('ficheros:parametros:emergencia:tipo-documento:index:%s:%s:%s:%s:%s', $version, $page, $perPage, $q ?? '', $status ?? '');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($filters, $perPage, $page) {
            $q = isset($filters['q']) ? trim((string) $filters['q']) : null;
            $status = isset($filters['status']) ? trim((string) $filters['status']) : null;

            $query = TipoDocumento::query();

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
