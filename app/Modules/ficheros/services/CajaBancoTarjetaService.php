<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\CajaBancoTarjeta;
use App\Modules\admision\models\CajaMedioPago;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CajaBancoTarjetaService
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
        $last = CajaBancoTarjeta::query()
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
    private const CACHE_VERSION_KEY = 'ficheros:parametros:caja:banco-tarjeta:version';

    private function getListCacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 0);
    }

    private function invalidateListCache(): void
    {
        Cache::put(self::CACHE_VERSION_KEY, $this->getListCacheVersion() + 1, 86400);
    }

    public function mediosDisponiblesPorFormas(array $formaPagoIds): array
    {
        $ids = collect($formaPagoIds)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        $rows = CajaMedioPago::query()
            ->where('estado', RecordStatus::ACTIVO->value)
            ->whereHas('formasPago', function ($q) use ($ids) {
                $q->whereIn('caja_formas_pago.id', $ids);
            })
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'descripcion', 'estado']);

        return $rows->map(fn ($m) => [
            'id' => (int) $m->id,
            'codigo' => (string) $m->codigo,
            'descripcion' => (string) $m->descripcion,
            'estado' => (string) $m->estado,
        ])->values()->all();
    }

    private function assertMediosVinculadosAFormas(array $formaPagoIds, array $medioPagoIds): void
    {
        $formaSet = collect($formaPagoIds)->map(fn ($id) => (int) $id)->unique()->values()->all();
        $medioSet = collect($medioPagoIds)->map(fn ($id) => (int) $id)->unique()->values()->all();
        if ($formaSet === [] || $medioSet === []) {
            throw ValidationException::withMessages([
                'medio_pago_ids' => 'Selecciona al menos una forma de pago y un medio de pago compatibles.',
            ]);
        }

        $medios = CajaMedioPago::query()
            ->whereIn('id', $medioSet)
            ->with('formasPago:id')
            ->get();

        if ($medios->count() !== count($medioSet)) {
            throw ValidationException::withMessages([
                'medio_pago_ids' => 'Uno o más medios de pago no son válidos.',
            ]);
        }

        foreach ($medios as $medio) {
            $medioFormaIds = $medio->formasPago->pluck('id')->map(fn ($id) => (int) $id)->all();
            $inter = array_intersect($medioFormaIds, $formaSet);
            if ($inter === []) {
                throw ValidationException::withMessages([
                    'medio_pago_ids' => 'El medio de pago "'.$medio->descripcion.'" no está relacionado con las formas de pago seleccionadas.',
                ]);
            }
        }
    }

    private function toPayload(CajaBancoTarjeta $row): array
    {
        $row->loadMissing(['formasPago', 'mediosPago']);
        $formas = $row->formasPago->sortBy('codigo')->values();
        $medios = $row->mediosPago->sortBy('codigo')->values();
        $formaLabels = $formas->map(fn ($f) => (string) $f->descripcion)->values()->all();
        $medioLabels = $medios->map(fn ($m) => (string) $m->descripcion)->values()->all();
        $secondary = implode(' · ', array_filter([
            $formaLabels !== [] ? implode(' · ', $formaLabels) : null,
            $medioLabels !== [] ? implode(' · ', $medioLabels) : null,
        ], fn ($x) => $x !== null && $x !== ''));

        return [
            'id' => (int) $row->id,
            'codigo' => (string) $row->codigo,
            'descripcion' => (string) $row->descripcion,
            'estado' => (string) $row->estado,
            'forma_pago_ids' => $formas->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'medio_pago_ids' => $medios->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'forma_pago_labels' => $formaLabels,
            'medio_pago_labels' => $medioLabels,
            'resumen_secundario' => $secondary,
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
        $cacheKey = sprintf('ficheros:parametros:caja:banco-tarjeta:index:%s:%s:%s:%s:%s', $version, $page, $perPage, $q ?? '', $status ?? '');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($filters, $perPage, $page) {
            $q = isset($filters['q']) ? trim((string) $filters['q']) : null;
            $status = isset($filters['status']) ? trim((string) $filters['status']) : null;

            $query = CajaBancoTarjeta::query()->with(['formasPago', 'mediosPago']);

            if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
                $query->where('estado', $status);
            }

            if ($q !== null && $q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('codigo', 'ilike', "%{$q}%")
                        ->orWhere('descripcion', 'ilike', "%{$q}%")
                        ->orWhereHas('formasPago', function ($f) use ($q) {
                            $f->where('descripcion', 'ilike', "%{$q}%");
                        })
                        ->orWhereHas('mediosPago', function ($m) use ($q) {
                            $m->where('descripcion', 'ilike', "%{$q}%");
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
            $this->assertMediosVinculadosAFormas($data['forma_pago_ids'], $data['medio_pago_ids']);

            $codigo = isset($data['codigo']) && trim((string) $data['codigo']) !== ''
                ? trim((string) $data['codigo'])
                : $this->peekNextCodigo();

            $row = CajaBancoTarjeta::create([
                'codigo' => $codigo,
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $row->formasPago()->sync(collect($data['forma_pago_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all());
            $row->mediosPago()->sync(collect($data['medio_pago_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all());

            $payload = $this->toPayload($row->fresh(['formasPago', 'mediosPago']));

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.banco-tarjeta.create',
                'Crear banco o tarjeta',
                'caja_banco_tarjeta',
                (string) $row->id,
                $payload,
                'success',
                201
            );

            $this->invalidateListCache();

            return $payload;
        });
    }

    public function update(CajaBancoTarjeta $row, array $data): array
    {
        return DB::transaction(function () use ($row, $data) {
            $this->assertMediosVinculadosAFormas($data['forma_pago_ids'], $data['medio_pago_ids']);

            $before = $this->toPayload($row->load(['formasPago', 'mediosPago']));

            $row->fill([
                'codigo' => $data['codigo'],
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);
            $row->save();

            $row->formasPago()->sync(collect($data['forma_pago_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all());
            $row->mediosPago()->sync(collect($data['medio_pago_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all());

            $after = $this->toPayload($row->fresh(['formasPago', 'mediosPago']));

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.banco-tarjeta.update',
                'Actualizar banco o tarjeta',
                'caja_banco_tarjeta',
                (string) $row->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();

            return $after;
        });
    }

    public function deactivate(CajaBancoTarjeta $row): array
    {
        return DB::transaction(function () use ($row) {
            $before = $row->only(['estado']);
            $row->estado = RecordStatus::INACTIVO->value;
            $row->save();
            $payload = $this->toPayload($row->fresh(['formasPago', 'mediosPago']));

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.banco-tarjeta.deactivate',
                'Desactivar banco o tarjeta',
                'caja_banco_tarjeta',
                (string) $row->id,
                ['before' => $before, 'after' => ['estado' => $row->estado]],
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
            if ($it instanceof CajaBancoTarjeta) {
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
