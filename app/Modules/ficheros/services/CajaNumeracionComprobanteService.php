<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\CajaNumeracionComprobante;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CajaNumeracionComprobanteService
{
    public function __construct(
        private AuditService $audit,
    ) {}

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const CACHE_VERSION_KEY = 'ficheros:parametros:caja:numeracion-comprobante:version';

    private function getListCacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 0);
    }

    private function invalidateListCache(): void
    {
        Cache::put(self::CACHE_VERSION_KEY, $this->getListCacheVersion() + 1, 86400);
    }

    private function numeroFormateado(int $numero): string
    {
        return str_pad((string) max(1, min(9_999_999, $numero)), 7, '0', STR_PAD_LEFT);
    }

    private function enrichRow(CajaNumeracionComprobante $row, ?int $numeroOverride = null): array
    {
        $tipo = $row->tipoDocumento;
        $numero = $numeroOverride ?? (int) $row->numero;
        $numeroFmt = $this->numeroFormateado($numero);
        return [
            'id' => $row->id,
            'tipo_documento_id' => $row->tipo_documento_id,
            'tipo_documento_codigo' => (string) ($tipo?->codigo ?? ''),
            'tipo_documento_descripcion' => (string) ($tipo?->descripcion ?? ''),
            'serie' => (string) $row->serie,
            'numero' => $numero,
            'numero_formateado' => $numeroFmt,
            'codigo' => (string) $row->serie,
            'descripcion' => trim(((string) ($tipo?->descripcion ?? '')).' · '.$numeroFmt),
            'estado' => (string) $row->estado,
            'created_at' => $row->created_at?->toISOString(),
            'updated_at' => $row->updated_at?->toISOString(),
        ];
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $q = isset($filters['q']) ? trim((string) $filters['q']) : null;
        $status = isset($filters['status']) ? trim((string) $filters['status']) : null;

        $version = $this->getListCacheVersion();
        $cacheKey = sprintf('ficheros:parametros:caja:numeracion-comprobante:index:%s:%s:%s:%s:%s', $version, $page, $perPage, $q ?? '', $status ?? '');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($perPage, $page, $q, $status) {
            $query = CajaNumeracionComprobante::query()->with('tipoDocumento');

            if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
                $query->where('estado', $status);
            }

            if ($q !== null && $q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('serie', 'ilike', "%{$q}%")
                        ->orWhereRaw("LPAD(numero::text, 7, '0') ilike ?", ["%{$q}%"])
                        ->orWhereHas('tipoDocumento', function ($tq) use ($q) {
                            $tq->where('codigo', 'ilike', "%{$q}%")
                                ->orWhere('descripcion', 'ilike', "%{$q}%");
                        });
                });
            }

            return $query->orderBy('serie')
                ->orderBy('numero')
                ->paginate($perPage, ['*'], 'page', $page)
                ->appends([
                    'per_page' => $perPage,
                    'q' => $q,
                    'status' => $status,
                ]);
        });
    }

    /**
     * Listado completo de numeraciones activas para pantallas de emisión (sin tope de paginación de 100).
     *
     * @return list<array<string, mixed>>
     */
    public function listAllActivosForEmision(int $limit = 2000): array
    {
        $limit = max(1, min(5000, $limit));

        $rows = CajaNumeracionComprobante::query()
            ->with('tipoDocumento')
            ->leftJoin(
                'caja_numeracion_comprobante_correlativos as cnc',
                'cnc.numeracion_comprobante_id',
                '=',
                'caja_numeraciones_comprobante.id'
            )
            ->addSelect('caja_numeraciones_comprobante.*')
            ->addSelect(DB::raw('COALESCE(cnc.next_numero, caja_numeraciones_comprobante.numero) as emision_numero'))
            ->where('estado', RecordStatus::ACTIVO->value)
            ->orderBy('serie')
            ->orderBy('numero')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->enrichRow($row, (int) ($row->emision_numero ?? $row->numero));
        }

        return $out;
    }

    private function assertUniqueSerie(int $tipoDocumentoId, string $serie, ?int $ignoreId = null): void
    {
        $exists = CajaNumeracionComprobante::query()
            ->where('tipo_documento_id', $tipoDocumentoId)
            ->where('serie', $serie)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'serie' => 'La serie ya existe para el tipo de documento seleccionado.',
            ]);
        }
    }

    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $this->assertUniqueSerie((int) $data['tipo_documento_id'], (string) $data['serie']);

            $row = CajaNumeracionComprobante::create([
                'tipo_documento_id' => (int) $data['tipo_documento_id'],
                'serie' => (string) $data['serie'],
                'numero' => (int) $data['numero'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);
            $row->load('tipoDocumento');

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.numeracion-comprobante.create',
                'Crear numeración de comprobante',
                'caja_numeracion_comprobante',
                (string) $row->id,
                $this->enrichRow($row),
                'success',
                201
            );

            $this->invalidateListCache();

            return $this->enrichRow($row);
        });
    }

    public function update(CajaNumeracionComprobante $row, array $data): array
    {
        return DB::transaction(function () use ($row, $data) {
            $before = $this->enrichRow($row->load('tipoDocumento'));
            $this->assertUniqueSerie((int) $data['tipo_documento_id'], (string) $data['serie'], (int) $row->id);

            $row->fill([
                'tipo_documento_id' => (int) $data['tipo_documento_id'],
                'serie' => (string) $data['serie'],
                'numero' => (int) $data['numero'],
                'estado' => (string) $data['estado'],
            ]);
            $row->save();
            $row->load('tipoDocumento');
            $after = $this->enrichRow($row);

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.numeracion-comprobante.update',
                'Actualizar numeración de comprobante',
                'caja_numeracion_comprobante',
                (string) $row->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();

            return $after;
        });
    }

    public function deactivate(CajaNumeracionComprobante $row): array
    {
        return DB::transaction(function () use ($row) {
            $before = ['estado' => $row->estado];
            $row->estado = RecordStatus::INACTIVO->value;
            $row->save();
            $row->load('tipoDocumento');
            $after = $this->enrichRow($row);

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.numeracion-comprobante.deactivate',
                'Desactivar numeración de comprobante',
                'caja_numeracion_comprobante',
                (string) $row->id,
                ['before' => $before, 'after' => ['estado' => $row->estado]],
                'success',
                200
            );

            $this->invalidateListCache();

            return $after;
        });
    }

    public function serializePage(LengthAwarePaginator $p): array
    {
        $items = [];
        foreach ($p->items() as $it) {
            if ($it instanceof CajaNumeracionComprobante) {
                $it->loadMissing('tipoDocumento');
                $items[] = $this->enrichRow($it);
            }
        }

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ];
    }
}
