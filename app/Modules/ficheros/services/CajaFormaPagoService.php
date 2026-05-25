<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\CajaFormaPago;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CajaFormaPagoService
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
        $last = CajaFormaPago::query()
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
    private const CACHE_VERSION_KEY = 'ficheros:parametros:caja:forma-pago:version';

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
        $cacheKey = 'ficheros:parametros:caja:forma-pago:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = CajaFormaPago::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'descripcion']);
            $this->applyListingSort($query, $params, ['codigo', 'descripcion', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function listAllActivosForEmision(int $limit = 2000): array
    {
        $limit = max(1, min(5000, $limit));

        $rows = CodigoCorrelativo::orderByCodigoAsc(
            CajaFormaPago::query()->where('estado', RecordStatus::ACTIVO->value)
        )->limit($limit)->get();

        return $rows->map(fn (CajaFormaPago $row) => [
            'id' => (int) $row->id,
            'codigo' => (string) $row->codigo,
            'descripcion' => (string) $row->descripcion,
            'estado' => (string) $row->estado,
            'created_at' => $row->created_at?->toISOString(),
            'updated_at' => $row->updated_at?->toISOString(),
        ])->values()->all();
    }

    public function create(array $data): CajaFormaPago
    {
        return DB::transaction(function () use ($data) {
            $codigo = isset($data['codigo']) && trim((string) $data['codigo']) !== ''
                ? trim((string) $data['codigo'])
                : $this->peekNextCodigo();
            $formaPago = CajaFormaPago::create([
                'codigo' => $codigo,
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.forma-pago.create',
                'Crear forma de pago',
                'caja_forma_pago',
                (string) $formaPago->id,
                [
                    'codigo' => $formaPago->codigo,
                    'descripcion' => $formaPago->descripcion,
                    'estado' => $formaPago->estado,
                ],
                'success',
                201
            );

            $this->invalidateListCache();

            return $formaPago;
        });
    }

    public function update(CajaFormaPago $formaPago, array $data): CajaFormaPago
    {
        return DB::transaction(function () use ($formaPago, $data) {
            $before = $formaPago->only(['codigo', 'descripcion', 'estado']);

            $formaPago->fill([
                'codigo' => $data['codigo'],
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);
            $formaPago->save();

            $after = $formaPago->only(['codigo', 'descripcion', 'estado']);

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.forma-pago.update',
                'Actualizar forma de pago',
                'caja_forma_pago',
                (string) $formaPago->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();

            return $formaPago;
        });
    }

    public function deactivate(CajaFormaPago $formaPago): CajaFormaPago
    {
        return DB::transaction(function () use ($formaPago) {
            $before = $formaPago->only(['estado']);
            $formaPago->estado = RecordStatus::INACTIVO->value;
            $formaPago->save();

            $this->audit->log(
                'masterdata.ficheros.parametros.caja.forma-pago.deactivate',
                'Desactivar forma de pago',
                'caja_forma_pago',
                (string) $formaPago->id,
                ['before' => $before, 'after' => $formaPago->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();

            return $formaPago;
        });
    }
}
