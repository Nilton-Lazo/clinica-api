<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Contratante;
use App\Modules\admision\models\Iafa;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TipoCliente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TipoClienteService
{
    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        if ($n < 1000) {
            return str_pad((string)$n, 3, '0', STR_PAD_LEFT);
        }
        return (string)$n;
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

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $status = isset($filters['status']) ? trim((string)$filters['status']) : null;

        $query = TipoCliente::query();

        if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
            $query->where('estado', $status);
        }

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('descripcion_tipo_cliente', 'ilike', "%{$q}%");
            });
        }

        return $query
            ->orderByRaw('CAST(codigo AS INTEGER) ASC')
            ->paginate($perPage)
            ->appends([
                'per_page' => $perPage,
                'q' => $q,
                'status' => $status,
            ]);
    }

    private function loadTarifaForTipoCliente(int $tarifaId): Tarifa
    {
        $tarifa = Tarifa::query()->find($tarifaId);

        if (!$tarifa) {
            throw ValidationException::withMessages(['tarifa_id' => ['Tarifa no existe.']]);
        }

        if ($tarifa->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['tarifa_id' => ['Tarifa debe estar ACTIVA.']]);
        }

        if ($tarifa->iafa_id === null) {
            throw ValidationException::withMessages(['tarifa_id' => ['Esta Tarifa no tiene IAFAS asociada y no puede usarse para Tipos de cliente.']]);
        }

        $iafa = Iafa::query()->find($tarifa->iafa_id);
        if (!$iafa || $iafa->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['tarifa_id' => ['La IAFAS asociada a la Tarifa debe estar ACTIVA.']]);
        }

        return $tarifa;
    }

    private function loadContratanteActivo(int $contratanteId): Contratante
    {
        $c = Contratante::query()->find($contratanteId);

        if (!$c) {
            throw ValidationException::withMessages(['contratante_id' => ['Contratante no existe.']]);
        }

        if ($c->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['contratante_id' => ['Contratante debe estar ACTIVO.']]);
        }

        return $c;
    }

    private function buildDescripcion(Contratante $contratante, Tarifa $tarifa): string
    {
        $left = trim((string)$contratante->razon_social);
        $right = trim((string)$tarifa->descripcion_tarifa);

        $desc = $left . '/' . $right;

        if ($left === '' || $right === '') {
            throw ValidationException::withMessages(['descripcion_tipo_cliente' => ['No se pudo generar la descripción (faltan valores).']]);
        }

        if (mb_strlen($desc) > 255) {
            throw ValidationException::withMessages(['descripcion_tipo_cliente' => ['La descripción autogenerada supera 255 caracteres.']]);
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

            return $tipoCliente;
        });
    }
}
