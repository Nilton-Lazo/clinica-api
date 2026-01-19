<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaCategoria;
use App\Modules\admision\models\TarifaSubcategoria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TarifaSubcategoriaService
{
    public function __construct(private AuditService $audit) {}

    private function assertTarifaActiva(Tarifa $tarifa): void
    {
        if ($tarifa->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['La tarifa debe estar ACTIVA para operar subcategorías.'],
            ]);
        }
    }

    private function assertBelongsTarifa(Tarifa $tarifa, TarifaSubcategoria $sub): void
    {
        if ((int)$sub->tarifa_id !== (int)$tarifa->id) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['La subcategoría no pertenece a la tarifa indicada.'],
            ]);
        }
    }

    private function formatCodigo(int $n): string
    {
        if ($n < 1 || $n > 99) {
            throw new \RuntimeException('No se pudo generar el código de subcategoría: excede 2 dígitos (01-99).');
        }
        return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
    }

    private function findCategoriaActiva(Tarifa $tarifa, int $categoriaId): TarifaCategoria
    {
        $cat = TarifaCategoria::query()
            ->where('tarifa_id', $tarifa->id)
            ->where('id', $categoriaId)
            ->first();

        if (!$cat) {
            throw ValidationException::withMessages(['categoria_id' => ['Categoría no existe en esta tarifa.']]);
        }

        if ($cat->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['categoria_id' => ['La categoría debe estar ACTIVA.']]);
        }

        return $cat;
    }

    public function peekNextCodigo(Tarifa $tarifa, int $categoriaId): string
    {
        if ($categoriaId < 1) {
            throw ValidationException::withMessages(['categoria_id' => ['categoria_id es requerido.']]);
        }

        $last = TarifaSubcategoria::query()
            ->where('tarifa_id', $tarifa->id)
            ->where('categoria_id', $categoriaId)
            ->whereRaw("codigo ~ '^[0-9]+$'")
            ->orderByRaw("codigo::int desc")
            ->value('codigo');

        $n = $last ? (int)$last : 0;
        return $this->formatCodigo($n + 1);
    }

    public function paginate(Tarifa $tarifa, array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $status = isset($filters['status']) ? trim((string)$filters['status']) : null;
        $categoriaId = isset($filters['categoria_id']) ? (int)$filters['categoria_id'] : 0;

        $query = TarifaSubcategoria::query()->where('tarifa_id', $tarifa->id);

        if ($categoriaId > 0) {
            $query->where('categoria_id', $categoriaId);
        }

        if ($status && in_array($status, RecordStatus::values(), true)) {
            $query->where('estado', $status);
        }

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('nombre', 'ilike', "%{$q}%");
            });
        }

        return $query->orderBy('categoria_id')->orderBy('codigo')->paginate($perPage)->appends([
            'per_page' => $perPage,
            'q' => $q,
            'status' => $status,
            'categoria_id' => $categoriaId,
        ]);
    }

    public function lookup(Tarifa $tarifa, int $categoriaId, bool $onlyActivas = true): array
    {
        if ($categoriaId < 1) {
            return [];
        }

        $q = TarifaSubcategoria::query()
            ->where('tarifa_id', $tarifa->id)
            ->where('categoria_id', $categoriaId)
            ->when($onlyActivas, fn($x) => $x->where('estado', RecordStatus::ACTIVO->value))
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'estado']);

        return $q->map(fn($s) => [
            'id' => (int)$s->id,
            'codigo' => (string)$s->codigo,
            'descripcion' => (string)$s->nombre,
            'estado' => (string)$s->estado,
        ])->all();
    }

    public function create(Tarifa $tarifa, array $data): TarifaSubcategoria
    {
        $this->assertTarifaActiva($tarifa);

        return DB::transaction(function () use ($tarifa, $data) {
            DB::statement('LOCK TABLE tarifa_subcategorias IN EXCLUSIVE MODE');

            $categoriaId = (int)$data['categoria_id'];
            $cat = $this->findCategoriaActiva($tarifa, $categoriaId);

            $codigo = $this->peekNextCodigo($tarifa, $cat->id);

            $sub = TarifaSubcategoria::create([
                'tarifa_id' => $tarifa->id,
                'categoria_id' => $cat->id,
                'codigo' => $codigo,
                'nombre' => $data['descripcion'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.tarifario.subcategorias.create',
                'Crear subcategoría de tarifario',
                'tarifa_subcategoria',
                (string)$sub->id,
                [
                    'tarifa_id' => (int)$tarifa->id,
                    'categoria_id' => (int)$sub->categoria_id,
                    'codigo' => $sub->codigo,
                    'descripcion' => $sub->nombre,
                    'estado' => $sub->estado,
                ],
                'success',
                201
            );

            return $sub;
        });
    }

    public function update(Tarifa $tarifa, TarifaSubcategoria $sub, array $data): TarifaSubcategoria
    {
        $this->assertTarifaActiva($tarifa);
        $this->assertBelongsTarifa($tarifa, $sub);

        return DB::transaction(function () use ($tarifa, $sub, $data) {
            $before = $sub->only(['categoria_id', 'codigo', 'nombre', 'estado']);

            $sub->fill([
                'nombre' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);
            $sub->save();

            if ($sub->estado !== RecordStatus::ACTIVO->value) {
                DB::table('tarifa_servicios')
                    ->where('tarifa_id', $tarifa->id)
                    ->where('subcategoria_id', $sub->id)
                    ->update(['estado' => $sub->estado, 'updated_at' => now()]);
            }

            $after = $sub->only(['categoria_id', 'codigo', 'nombre', 'estado']);

            $this->audit->log(
                'masterdata.admision.tarifario.subcategorias.update',
                'Actualizar subcategoría de tarifario',
                'tarifa_subcategoria',
                (string)$sub->id,
                [
                    'tarifa_id' => (int)$tarifa->id,
                    'before' => $before,
                    'after' => $after,
                ],
                'success',
                200
            );

            return $sub;
        });
    }

    public function deactivate(Tarifa $tarifa, TarifaSubcategoria $sub): TarifaSubcategoria
    {
        $this->assertTarifaActiva($tarifa);
        $this->assertBelongsTarifa($tarifa, $sub);

        return DB::transaction(function () use ($tarifa, $sub) {
            $before = $sub->only(['estado']);

            $sub->estado = RecordStatus::INACTIVO->value;
            $sub->save();

            DB::table('tarifa_servicios')
                ->where('tarifa_id', $tarifa->id)
                ->where('subcategoria_id', $sub->id)
                ->update(['estado' => RecordStatus::INACTIVO->value, 'updated_at' => now()]);

            $this->audit->log(
                'masterdata.admision.tarifario.subcategorias.deactivate',
                'Desactivar subcategoría de tarifario',
                'tarifa_subcategoria',
                (string)$sub->id,
                [
                    'tarifa_id' => (int)$tarifa->id,
                    'before' => $before,
                    'after' => $sub->only(['estado']),
                ],
                'success',
                200
            );

            return $sub;
        });
    }
}
