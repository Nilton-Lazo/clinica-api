<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\Cliente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ClienteService
{
    use AppliesListingQuery;

    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        return CodigoCorrelativo::format($n);
    }

    private function nextCodigoInt(): int
    {
        $last = Cliente::query()
            ->select('codigo')
            ->orderByRaw('CAST(codigo AS INTEGER) DESC')
            ->value('codigo');

        $lastInt = $last !== null ? (int) $last : 0;

        return $lastInt + 1;
    }

    public function previewNextCodigo(): string
    {
        return $this->formatCodigo($this->nextCodigoInt());
    }

    private const INDEX_CACHE_TTL_SECONDS = 30;

    private const CACHE_VERSION_KEY = 'ficheros:clientes:version';

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
        $cacheKey = 'ficheros:clientes:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = Cliente::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'nombre', 'dni_o_ruc', 'telefono', 'direccion']);
            $this->applyListingSort($query, $params, ['codigo', 'nombre', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): Cliente
    {
        return DB::transaction(function () use ($data) {
            DB::statement('LOCK TABLE clientes IN EXCLUSIVE MODE');

            $codigo = $this->formatCodigo($this->nextCodigoInt());

            $cliente = Cliente::create([
                'codigo' => $codigo,
                'tipo' => $data['tipo'],
                'nombre' => $data['nombre'],
                'dni_o_ruc' => $data['dni_o_ruc'],
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.ficheros.clientes.create',
                'Crear cliente',
                'cliente',
                (string) $cliente->id,
                $cliente->only(['codigo', 'tipo', 'nombre', 'dni_o_ruc', 'telefono', 'direccion', 'estado']),
                'success',
                201
            );

            $this->invalidateListCache();

            return $cliente;
        });
    }

    public function update(Cliente $cliente, array $data): Cliente
    {
        return DB::transaction(function () use ($cliente, $data) {
            $before = $cliente->only(['tipo', 'nombre', 'dni_o_ruc', 'telefono', 'direccion', 'estado']);

            $cliente->fill([
                'tipo' => $data['tipo'],
                'nombre' => $data['nombre'],
                'dni_o_ruc' => $data['dni_o_ruc'],
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'estado' => $data['estado'],
            ]);
            $cliente->save();

            $after = $cliente->only(['tipo', 'nombre', 'dni_o_ruc', 'telefono', 'direccion', 'estado']);

            $this->audit->log(
                'masterdata.ficheros.clientes.update',
                'Actualizar cliente',
                'cliente',
                (string) $cliente->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();

            return $cliente;
        });
    }

    public function deactivate(Cliente $cliente): Cliente
    {
        return DB::transaction(function () use ($cliente) {
            $before = $cliente->only(['estado']);

            $cliente->estado = RecordStatus::INACTIVO->value;
            $cliente->save();

            $this->audit->log(
                'masterdata.ficheros.clientes.deactivate',
                'Desactivar cliente',
                'cliente',
                (string) $cliente->id,
                ['before' => $before, 'after' => $cliente->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();

            return $cliente;
        });
    }
}
