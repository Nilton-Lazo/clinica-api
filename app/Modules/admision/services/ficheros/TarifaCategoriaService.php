<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaCategoria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TarifaCategoriaService
{
    public function __construct(private AuditService $audit) {}

    private function assertTarifaActiva(Tarifa $tarifa): void
    {
        if ($tarifa->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['La tarifa debe estar ACTIVA para operar categorías.'],
            ]);
        }
    }

    private function assertBelongs(Tarifa $tarifa, TarifaCategoria $categoria): void
    {
        if ((int)$categoria->tarifa_id !== (int)$tarifa->id) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['La categoría no pertenece a la tarifa indicada.'],
            ]);
        }
    }

    private function formatCodigo(int $n): string
    {
        if ($n < 1 || $n > 99) {
            throw new \RuntimeException('No se pudo generar el código de categoría: excede 2 dígitos (01-99).');
        }
        return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
    }

    public function peekNextCodigo(Tarifa $tarifa): string
    {
        $last = TarifaCategoria::query()
            ->where('tarifa_id', $tarifa->id)
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

        $query = TarifaCategoria::query()->where('tarifa_id', $tarifa->id);

        if ($status && in_array($status, RecordStatus::values(), true)) {
            $query->where('estado', $status);
        }

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('nombre', 'ilike', "%{$q}%");
            });
        }

        return $query->orderBy('codigo')->paginate($perPage)->appends([
            'per_page' => $perPage,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function lookup(Tarifa $tarifa, bool $onlyActivas = true): array
    {
        $q = TarifaCategoria::query()
            ->where('tarifa_id', $tarifa->id)
            ->when($onlyActivas, fn($x) => $x->where('estado', RecordStatus::ACTIVO->value))
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'estado']);

        return $q->map(fn($c) => [
            'id' => (int)$c->id,
            'codigo' => (string)$c->codigo,
            'descripcion' => (string)$c->nombre,
            'estado' => (string)$c->estado,
        ])->all();
    }

    public function create(Tarifa $tarifa, array $data): TarifaCategoria
    {
        $this->assertTarifaActiva($tarifa);

        return DB::transaction(function () use ($tarifa, $data) {
            DB::statement('LOCK TABLE tarifa_categorias IN EXCLUSIVE MODE');

            $codigo = $this->peekNextCodigo($tarifa);

            $categoria = TarifaCategoria::create([
                'tarifa_id' => $tarifa->id,
                'codigo' => $codigo,
                'nombre' => $data['descripcion'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.tarifario.categorias.create',
                'Crear categoría de tarifario',
                'tarifa_categoria',
                (string)$categoria->id,
                [
                    'tarifa_id' => (int)$tarifa->id,
                    'codigo' => $categoria->codigo,
                    'descripcion' => $categoria->nombre,
                    'estado' => $categoria->estado,
                ],
                'success',
                201
            );

            return $categoria;
        });
    }

    public function update(Tarifa $tarifa, TarifaCategoria $categoria, array $data): TarifaCategoria
    {
        $this->assertTarifaActiva($tarifa);
        $this->assertBelongs($tarifa, $categoria);

        return DB::transaction(function () use ($tarifa, $categoria, $data) {
            $before = $categoria->only(['codigo', 'nombre', 'estado']);

            $categoria->fill([
                'nombre' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);
            $categoria->save();

            if ($categoria->estado !== RecordStatus::ACTIVO->value) {
                DB::table('tarifa_subcategorias')
                    ->where('tarifa_id', $tarifa->id)
                    ->where('categoria_id', $categoria->id)
                    ->update(['estado' => $categoria->estado, 'updated_at' => now()]);

                DB::table('tarifa_servicios')
                    ->where('tarifa_id', $tarifa->id)
                    ->where('categoria_id', $categoria->id)
                    ->update(['estado' => $categoria->estado, 'updated_at' => now()]);
            }

            $after = $categoria->only(['codigo', 'nombre', 'estado']);

            $this->audit->log(
                'masterdata.admision.tarifario.categorias.update',
                'Actualizar categoría de tarifario',
                'tarifa_categoria',
                (string)$categoria->id,
                [
                    'tarifa_id' => (int)$tarifa->id,
                    'before' => $before,
                    'after' => $after,
                ],
                'success',
                200
            );

            return $categoria;
        });
    }

    public function deactivate(Tarifa $tarifa, TarifaCategoria $categoria): TarifaCategoria
    {
        $this->assertTarifaActiva($tarifa);
        $this->assertBelongs($tarifa, $categoria);

        return DB::transaction(function () use ($tarifa, $categoria) {
            $before = $categoria->only(['estado']);

            $categoria->estado = RecordStatus::INACTIVO->value;
            $categoria->save();

            // cascada
            DB::table('tarifa_subcategorias')
                ->where('tarifa_id', $tarifa->id)
                ->where('categoria_id', $categoria->id)
                ->update(['estado' => RecordStatus::INACTIVO->value, 'updated_at' => now()]);

            DB::table('tarifa_servicios')
                ->where('tarifa_id', $tarifa->id)
                ->where('categoria_id', $categoria->id)
                ->update(['estado' => RecordStatus::INACTIVO->value, 'updated_at' => now()]);

            $this->audit->log(
                'masterdata.admision.tarifario.categorias.deactivate',
                'Desactivar categoría de tarifario',
                'tarifa_categoria',
                (string)$categoria->id,
                [
                    'tarifa_id' => (int)$tarifa->id,
                    'before' => $before,
                    'after' => $categoria->only(['estado']),
                ],
                'success',
                200
            );

            return $categoria;
        });
    }
}
