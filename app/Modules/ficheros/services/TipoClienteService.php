<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\Contratante;
use App\Modules\admision\models\Iafa;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TipoCliente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TipoClienteService
{
    use AppliesListingQuery;

    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        return CodigoCorrelativo::format($n);
    }

    private function nextCodigoInt(): int
    {
        $last = TipoCliente::query()
            ->select('codigo')
            ->orderByRaw('CAST(codigo AS INTEGER) DESC')
            ->value('codigo');

        $lastInt = $last !== null ? (int)$last : 0;

        return $lastInt + 1;
    }

    public function previewNextCodigo(): string
    {
        return $this->formatCodigo($this->nextCodigoInt());
    }

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const CACHE_VERSION_KEY = 'ficheros:tipos_clientes:version';

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
        $cacheKey = 'ficheros:tipos_clientes:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = TipoCliente::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'descripcion_tipo_cliente']);
            $this->applyListingSort($query, $params, ['codigo', 'descripcion', 'estado'], 'codigo', ['descripcion' => 'descripcion_tipo_cliente']);

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    private function loadTarifaForTipoCliente(int $tarifaId): Tarifa
    {
        $tarifa = Tarifa::query()->find($tarifaId);

        if (!$tarifa) {
            throw ValidationException::withMessages(['tarifa_id' => ['La tarifa seleccionada no existe o ya no está disponible.']]);
        }

        if ($tarifa->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['tarifa_id' => ['La tarifa seleccionada debe estar activa para crear tipos de cliente.']]);
        }

        if ($tarifa->iafa_id === null) {
            throw ValidationException::withMessages(['tarifa_id' => ['La tarifa seleccionada no tiene IAFAS asociada y no puede usarse para tipos de cliente.']]);
        }

        $iafa = Iafa::query()->find($tarifa->iafa_id);
        if (!$iafa || $iafa->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['tarifa_id' => ['La IAFAS asociada a la tarifa debe existir y estar activa para crear tipos de cliente.']]);
        }

        return $tarifa;
    }

    private function loadContratanteActivo(int $contratanteId): Contratante
    {
        $c = Contratante::query()->find($contratanteId);

        if (!$c) {
            throw ValidationException::withMessages(['contratante_id' => ['El contratante seleccionado no existe o ya no está disponible.']]);
        }

        if ($c->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['contratante_id' => ['El contratante seleccionado debe estar activo para crear tipos de cliente.']]);
        }

        return $c;
    }

    private function buildDescripcion(Contratante $contratante, Tarifa $tarifa): string
    {
        $left = trim((string)$contratante->razon_social);
        $right = trim((string)$tarifa->descripcion_tarifa);

        $desc = $left . '/' . $right;

        if ($left === '' || $right === '') {
            throw ValidationException::withMessages(['descripcion_tipo_cliente' => ['No se pudo generar la descripción del tipo de cliente porque faltan datos del contratante o la tarifa.']]);
        }

        if (mb_strlen($desc) > 255) {
            throw ValidationException::withMessages(['descripcion_tipo_cliente' => ['La descripción generada con contratante y tarifa supera 255 caracteres. Reduce alguno de esos nombres.']]);
        }

        return $desc;
    }

    public function create(array $data): TipoCliente
    {
        return DB::transaction(function () use ($data) {
            DB::statement('LOCK TABLE tipos_clientes IN EXCLUSIVE MODE');

            $codigo = $this->formatCodigo($this->nextCodigoInt());

            $tarifa = $this->loadTarifaForTipoCliente((int)$data['tarifa_id']);
            $contratante = $this->loadContratanteActivo((int)$data['contratante_id']);

            $iafaId = (int)$tarifa->iafa_id;
            $descripcion = $this->buildDescripcion($contratante, $tarifa);

            $tipoCliente = TipoCliente::create([
                'codigo' => $codigo,
                'tarifa_id' => (int)$tarifa->id,
                'iafa_id' => $iafaId,
                'contratante_id' => (int)$contratante->id,
                'descripcion_tipo_cliente' => $descripcion,
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.tipos_cliente.create',
                'Crear tipo de cliente',
                'tipo_cliente',
                (string)$tipoCliente->id,
                $tipoCliente->only([
                    'codigo',
                    'tarifa_id',
                    'iafa_id',
                    'contratante_id',
                    'descripcion_tipo_cliente',
                    'estado',
                ]),
                'success',
                201
            );

            $this->invalidateListCache();
            return $tipoCliente;
        });
    }

    public function update(TipoCliente $tipoCliente, array $data): TipoCliente
    {
        return DB::transaction(function () use ($tipoCliente, $data) {
            $before = $tipoCliente->only([
                'tarifa_id',
                'iafa_id',
                'contratante_id',
                'descripcion_tipo_cliente',
                'estado',
            ]);

            $tarifa = $this->loadTarifaForTipoCliente((int)$data['tarifa_id']);
            $contratante = $this->loadContratanteActivo((int)$data['contratante_id']);

            $iafaId = (int)$tarifa->iafa_id;
            $descripcion = $this->buildDescripcion($contratante, $tarifa);

            $tipoCliente->fill([
                'tarifa_id' => (int)$tarifa->id,
                'iafa_id' => $iafaId,
                'contratante_id' => (int)$contratante->id,
                'descripcion_tipo_cliente' => $descripcion,
                'estado' => $data['estado'],
            ]);

            $tipoCliente->save();

            $after = $tipoCliente->only([
                'tarifa_id',
                'iafa_id',
                'contratante_id',
                'descripcion_tipo_cliente',
                'estado',
            ]);

            $this->audit->log(
                'masterdata.admision.tipos_cliente.update',
                'Actualizar tipo de cliente',
                'tipo_cliente',
                (string)$tipoCliente->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();
            return $tipoCliente;
        });
    }

    public function deactivate(TipoCliente $tipoCliente): TipoCliente
    {
        return DB::transaction(function () use ($tipoCliente) {
            $before = $tipoCliente->only(['estado']);

            $tipoCliente->estado = RecordStatus::INACTIVO->value;
            $tipoCliente->save();

            $this->audit->log(
                'masterdata.admision.tipos_cliente.deactivate',
                'Desactivar tipo de cliente',
                'tipo_cliente',
                (string)$tipoCliente->id,
                ['before' => $before, 'after' => $tipoCliente->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();
            return $tipoCliente;
        });
    }
}

