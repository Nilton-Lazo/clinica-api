<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\CajaMedioPago;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CajaMedioPagoService
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

    public function paginate(GridParams $params): LengthAwarePaginator
    {
        $version = $this->getListCacheVersion();
        $cacheKey = 'ficheros:parametros:caja:medio-pago:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = CajaMedioPago::query()->with('formasPago');
            $this->applyListingStatus($query, $params);

            if ($params->q !== null) {
                $term = $params->q;
                $query->where(function ($sub) use ($term) {
                    $sub->where('codigo', 'ilike', "%{$term}%")
                        ->orWhere('descripcion', 'ilike', "%{$term}%")
                        ->orWhereHas('formasPago', function ($f) use ($term) {
                            $f->where('codigo', 'ilike', "%{$term}%")
                                ->orWhere('descripcion', 'ilike', "%{$term}%");
                        });
                });
            }

            $this->applyListingSort($query, $params, ['codigo', 'descripcion', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function listAllActivosForEmision(int $limit = 2000): array
    {
        $limit = max(1, min(5000, $limit));

        $rows = CodigoCorrelativo::orderByCodigoAsc(
            CajaMedioPago::query()
                ->with('formasPago')
                ->where('estado', RecordStatus::ACTIVO->value)
        )->limit($limit)->get();

        return $rows->map(fn (CajaMedioPago $row) => $this->toPayload($row))->values()->all();
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
