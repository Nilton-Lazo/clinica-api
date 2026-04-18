<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\CajaMedioPago;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CajaMedioPagoService
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
        $last = CajaMedioPago::query()
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
    private const CACHE_VERSION_KEY = 'ficheros:parametros:caja:medio-pago:version';

    private function getListCacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 0);
    }

    private function invalidateListCache(): void
    {
        Cache::put(self::CACHE_VERSION_KEY, $this->getListCacheVersion() + 1, 86400);
    }

    private function toPayload(CajaMedioPago $medio): array
    {
        $medio->loadMissing('formasPago');
        $formas = $medio->formasPago->sortBy('codigo')->values();
        $labels = $formas->map(fn ($f) => (string) $f->descripcion)->values()->all();
        $ids = $formas->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        return [
            'id' => (int) $medio->id,
            'codigo' => (string) $medio->codigo,
            'descripcion' => (string) $medio->descripcion,
            'estado' => (string) $medio->estado,
            'forma_pago_ids' => $ids,
            'forma_pago_labels' => $labels,
            'created_at' => $medio->created_at?->toISOString(),
            'updated_at' => $medio->updated_at?->toISOString(),
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
        $cacheKey = sprintf('ficheros:parametros:caja:medio-pago:index:%s:%s:%s:%s:%s', $version, $page, $perPage, $q ?? '', $status ?? '');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($filters, $perPage, $page) {
            $q = isset($filters['q']) ? trim((string) $filters['q']) : null;
            $status = isset($filters['status']) ? trim((string) $filters['status']) : null;

            $query = CajaMedioPago::query()->with('formasPago');

            if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
                $query->where('estado', $status);
            }

            if ($q !== null && $q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('codigo', 'ilike', "%{$q}%")
                        ->orWhere('descripcion', 'ilike', "%{$q}%")
                        ->orWhereHas('formasPago', function ($f) use ($q) {
                            $f->where('codigo', 'ilike', "%{$q}%")
                                ->orWhere('descripcion', 'ilike', "%{$q}%");
                        });
                });
            }

            return $query->orderBy('codigo')->paginate($perPage, ['*'], 'page', $page)->appends([
                'per_page' => $perPage,
                'q' => $q,
                'status' => $status,
            ]);
        });
    }

    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $codigo = isset($data['codigo']) && trim((string) $data['codigo']) !== ''
                ? trim((string) $data['codigo'])
                : $this->peekNextCodigo();
            $medio = CajaMedioPago::create([
                'codigo' => $codigo,
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);
            $ids = collect($data['forma_pago_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
            $medio->formasPago()->sync($ids);
            $payload = $this->toPayload($medio);

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.medio-pago.create',
                'Crear medio de pago',
                'caja_medio_pago',
                (string) $medio->id,
                $payload,
                'success',
                201
            );

            $this->invalidateListCache();

            return $payload;
        });
    }

    public function update(CajaMedioPago $medio, array $data): array
    {
        return DB::transaction(function () use ($medio, $data) {
            $before = $this->toPayload($medio);
            $medio->fill([
                'codigo' => $data['codigo'],
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);
            $medio->save();
            $ids = collect($data['forma_pago_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
            $medio->formasPago()->sync($ids);
            $after = $this->toPayload($medio);

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.medio-pago.update',
                'Actualizar medio de pago',
                'caja_medio_pago',
                (string) $medio->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();

            return $after;
        });
    }

    public function deactivate(CajaMedioPago $medio): array
    {
        return DB::transaction(function () use ($medio) {
            $before = $medio->only(['estado']);
            $medio->estado = RecordStatus::INACTIVO->value;
            $medio->save();
            $payload = $this->toPayload($medio);

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.medio-pago.deactivate',
                'Desactivar medio de pago',
                'caja_medio_pago',
                (string) $medio->id,
                ['before' => $before, 'after' => ['estado' => $medio->estado]],
                'success',
                200
            );

            $this->invalidateListCache();

            return $payload;
        });
    }

    public function serializePage(LengthAwarePaginator $p): array
    {
        $items = [];
        foreach ($p->items() as $it) {
            if ($it instanceof CajaMedioPago) {
                $items[] = $this->toPayload($it);
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
