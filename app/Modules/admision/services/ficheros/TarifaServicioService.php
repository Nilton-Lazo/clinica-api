<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaCategoria;
use App\Modules\admision\models\TarifaServicio;
use App\Modules\admision\models\TarifaSubcategoria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TarifaServicioService
{
    public function __construct(private AuditService $audit) {}

    private function assertTarifaActiva(Tarifa $tarifa): void
    {
        if ($tarifa->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['La tarifa debe estar ACTIVA para operar servicios.'],
            ]);
        }
    }

    private function assertBelongs(Tarifa $tarifa, TarifaServicio $srv): void
    {
        if ((int)$srv->tarifa_id !== (int)$tarifa->id) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['El servicio no pertenece a la tarifa indicada.'],
            ]);
        }
    }

    private function format2(int $n, string $what): string
    {
        if ($n < 1 || $n > 99) {
            throw new \RuntimeException("No se pudo generar el código de {$what}: excede 2 dígitos (01-99).");
        }
        return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
    }

    private function normalizeNomenclador(?string $x): ?string
    {
        if ($x === null) return null;
        $t = strtoupper(trim($x));
        if ($t === '' || $t === 'NULL') return null;
        return $t;
    }

    private function assertCategoriaSubActivas(Tarifa $tarifa, int $categoriaId, int $subcategoriaId): array
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

        $sub = TarifaSubcategoria::query()
            ->where('tarifa_id', $tarifa->id)
            ->where('id', $subcategoriaId)
            ->where('categoria_id', $categoriaId)
            ->first();

        if (!$sub) {
            throw ValidationException::withMessages(['subcategoria_id' => ['Subcategoría no existe o no pertenece a la categoría.']]);
        }
        if ($sub->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['subcategoria_id' => ['La subcategoría debe estar ACTIVA.']]);
        }

        return [$cat, $sub];
    }

    public function peekNextCodigo(Tarifa $tarifa, int $categoriaId, int $subcategoriaId): array
    {
        if ($categoriaId < 1) throw ValidationException::withMessages(['categoria_id' => ['categoria_id es requerido.']]);
        if ($subcategoriaId < 1) throw ValidationException::withMessages(['subcategoria_id' => ['subcategoria_id es requerido.']]);

        [$cat, $sub] = $this->assertCategoriaSubActivas($tarifa, $categoriaId, $subcategoriaId);

        $last = TarifaServicio::query()
            ->where('tarifa_id', $tarifa->id)
            ->where('categoria_id', $categoriaId)
            ->where('subcategoria_id', $subcategoriaId)
            ->whereRaw("servicio_codigo ~ '^[0-9]+$'")
            ->orderByRaw("servicio_codigo::int desc")
            ->value('servicio_codigo');

        $n = $last ? (int)$last : 0;
        $servCodigo = $this->format2($n + 1, 'servicio');

        $codigoFull = ((string)$cat->codigo) . '.' . ((string)$sub->codigo) . '.' . $servCodigo;

        return [
            'servicio_codigo' => $servCodigo,
            'codigo' => $codigoFull,
        ];
    }

    public function paginate(Tarifa $tarifa, array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $status = isset($filters['status']) ? trim((string)$filters['status']) : null;
        $categoriaId = isset($filters['categoria_id']) ? (int)$filters['categoria_id'] : 0;
        $subcategoriaId = isset($filters['subcategoria_id']) ? (int)$filters['subcategoria_id'] : 0;

        $query = TarifaServicio::query()->where('tarifa_id', $tarifa->id);

        if ($categoriaId > 0) $query->where('categoria_id', $categoriaId);
        if ($subcategoriaId > 0) $query->where('subcategoria_id', $subcategoriaId);

        if ($status && in_array($status, RecordStatus::values(), true)) {
            $query->where('estado', $status);
        }

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('descripcion', 'ilike', "%{$q}%")
                    ->orWhere('nomenclador', 'ilike', "%{$q}%");
            });
        }

        return $query->orderBy('codigo')->paginate($perPage)->appends([
            'per_page' => $perPage,
            'q' => $q,
            'status' => $status,
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
        ]);
    }

    public function create(Tarifa $tarifa, array $data): TarifaServicio
    {
        $this->assertTarifaActiva($tarifa);

        return DB::transaction(function () use ($tarifa, $data) {
            DB::statement('LOCK TABLE tarifa_servicios IN EXCLUSIVE MODE');

            $categoriaId = (int)$data['categoria_id'];
            $subcategoriaId = (int)$data['subcategoria_id'];

            [$cat, $sub] = $this->assertCategoriaSubActivas($tarifa, $categoriaId, $subcategoriaId);

            $next = $this->peekNextCodigo($tarifa, $categoriaId, $subcategoriaId);

            $nom = $this->normalizeNomenclador($data['nomenclador'] ?? null);
            if ($nom !== null) {
                $exists = TarifaServicio::query()
                    ->where('tarifa_id', $tarifa->id)
                    ->where('nomenclador', $nom)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'nomenclador' => ['El nomenclador ya existe en esta tarifa.'],
                    ]);
                }
            }

            $srv = TarifaServicio::create([
                'tarifa_id' => $tarifa->id,
                'categoria_id' => $categoriaId,
                'subcategoria_id' => $subcategoriaId,

                'servicio_codigo' => $next['servicio_codigo'],
                'codigo' => $next['codigo'],

                'nomenclador' => $nom,
                'descripcion' => $data['descripcion'],
                'precio_sin_igv' => $data['precio_sin_igv'],
                'unidad' => $data['unidad'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.tarifario.servicios.create',
                'Crear servicio de tarifario',
                'tarifa_servicio',
                (string)$srv->id,
                [
                    'tarifa_id' => (int)$tarifa->id,
                    'categoria' => ['id' => (int)$cat->id, 'codigo' => (string)$cat->codigo],
                    'subcategoria' => ['id' => (int)$sub->id, 'codigo' => (string)$sub->codigo],
                    'codigo' => $srv->codigo,
                    'servicio_codigo' => $srv->servicio_codigo,
                    'nomenclador' => $srv->nomenclador,
                    'descripcion' => $srv->descripcion,
                    'precio_sin_igv' => $srv->precio_sin_igv,
                    'unidad' => $srv->unidad,
                    'estado' => $srv->estado,
                ],
                'success',
                201
            );

            return $srv;
        });
    }

    public function update(Tarifa $tarifa, TarifaServicio $srv, array $data): TarifaServicio
    {
        $this->assertTarifaActiva($tarifa);
        $this->assertBelongs($tarifa, $srv);

        return DB::transaction(function () use ($tarifa, $srv, $data) {
            $before = $srv->only(['nomenclador', 'descripcion', 'precio_sin_igv', 'unidad', 'estado']);

            $nom = $this->normalizeNomenclador($data['nomenclador'] ?? null);
            if ($nom !== null) {
                $exists = TarifaServicio::query()
                    ->where('tarifa_id', $tarifa->id)
                    ->where('nomenclador', $nom)
                    ->where('id', '<>', $srv->id)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'nomenclador' => ['El nomenclador ya existe en esta tarifa.'],
                    ]);
                }
            }

            if (($data['estado'] ?? $srv->estado) === RecordStatus::ACTIVO->value) {
                $this->assertCategoriaSubActivas($tarifa, (int)$srv->categoria_id, (int)$srv->subcategoria_id);
            }

            $srv->fill([
                'nomenclador' => $nom,
                'descripcion' => $data['descripcion'],
                'precio_sin_igv' => $data['precio_sin_igv'],
                'unidad' => $data['unidad'],
                'estado' => $data['estado'],
            ]);
            $srv->save();
            $srv->refresh();

            $after = $srv->only(['nomenclador', 'descripcion', 'precio_sin_igv', 'unidad', 'estado']);

            $this->audit->log(
                'masterdata.admision.tarifario.servicios.update',
                'Actualizar servicio de tarifario',
                'tarifa_servicio',
                (string)$srv->id,
                [
                    'tarifa_id' => (int)$tarifa->id,
                    'codigo' => $srv->codigo,
                    'before' => $before,
                    'after' => $after,
                ],
                'success',
                200
            );

            return $srv;
        });
    }

    public function deactivate(Tarifa $tarifa, TarifaServicio $srv): TarifaServicio
    {
        $this->assertTarifaActiva($tarifa);
        $this->assertBelongs($tarifa, $srv);

        return DB::transaction(function () use ($tarifa, $srv) {
            $before = $srv->only(['estado']);

            $srv->estado = RecordStatus::INACTIVO->value;
            $srv->save();
            $srv->refresh();

            $this->audit->log(
                'masterdata.admision.tarifario.servicios.deactivate',
                'Desactivar servicio de tarifario',
                'tarifa_servicio',
                (string)$srv->id,
                [
                    'tarifa_id' => (int)$tarifa->id,
                    'codigo' => $srv->codigo,
                    'before' => $before,
                    'after' => $srv->only(['estado']),
                ],
                'success',
                200
            );

            return $srv;
        });
    }
}
