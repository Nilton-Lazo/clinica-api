<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\support\RecordStatus;
use App\Modules\admision\models\Tarifa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class TarifarioCatalogoService
{
    public function listTarifasOperativas(array $filters = []): Collection
    {
        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;

        $query = Tarifa::query()
            ->select(['id', 'codigo', 'descripcion_tarifa', 'iafa_id', 'estado'])
            ->where('tarifa_base', false)
            ->where('estado', RecordStatus::ACTIVO->value);

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('descripcion_tarifa', 'ilike', "%{$q}%");
            });
        }

        return $query
            ->orderByRaw('CAST(codigo AS INTEGER) ASC')
            ->get();
    }

    public function getTarifaBase(): Tarifa
    {
        $base = Tarifa::query()
            ->where('tarifa_base', true)
            ->first();

        if (!$base) {
            throw ValidationException::withMessages([
                'tarifa_base' => ['No existe un tarifario base configurado.'],
            ]);
        }

        if ($base->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages([
                'tarifa_base' => ['El tarifario base debe estar ACTIVO.'],
            ]);
        }

        return $base;
    }

    private function assertTarifaOperativa(Tarifa $tarifa): void
    {
        if ($tarifa->tarifa_base) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['El tarifario base no puede usarse en esta pantalla (solo para clonación).'],
            ]);
        }

        if ($tarifa->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['La tarifa debe estar ACTIVA.'],
            ]);
        }
    }

    public function paginateServicios(Tarifa $tarifa, array $filters): LengthAwarePaginator
    {
        $this->assertTarifaOperativa($tarifa);

        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));

        $q = $filters['q'] ?? null;
        $codigo = $filters['codigo'] ?? null;
        $nomenclador = $filters['nomenclador'] ?? null;

        $status = $filters['status'] ?? null;
        $categoriaId = isset($filters['categoria_id']) ? (int)$filters['categoria_id'] : null;
        $subcategoriaId = isset($filters['subcategoria_id']) ? (int)$filters['subcategoria_id'] : null;

        $query = DB::table('tarifa_servicios AS ts')
            ->join('tarifa_categorias AS tc', 'tc.id', '=', 'ts.categoria_id')
            ->join('tarifa_subcategorias AS tsc', 'tsc.id', '=', 'ts.subcategoria_id')
            ->where('ts.tarifa_id', (int)$tarifa->id)
            ->select([
                'ts.id',
                'ts.codigo',
                'ts.nomenclador',
                'ts.descripcion',
                'ts.precio_sin_igv',
                'ts.unidad',
                'ts.estado',

                'tc.id AS categoria_id',
                'tc.codigo AS categoria_codigo',
                'tc.nombre AS categoria_nombre',

                'tsc.id AS subcategoria_id',
                'tsc.codigo AS subcategoria_codigo',
                'tsc.nombre AS subcategoria_nombre',
            ]);

        if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
            $query->where('ts.estado', $status);
        }

        if ($categoriaId !== null) {
            $query->where('ts.categoria_id', $categoriaId);
        }
        if ($subcategoriaId !== null) {
            $query->where('ts.subcategoria_id', $subcategoriaId);
        }

        if ($codigo !== null && $codigo !== '') {
            $query->where('ts.codigo', 'ilike', "%{$codigo}%");
        }

        if ($nomenclador !== null && $nomenclador !== '') {
            $query->where('ts.nomenclador', 'ilike', "%{$nomenclador}%");
        }

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('ts.codigo', 'ilike', "%{$q}%")
                    ->orWhere('ts.descripcion', 'ilike', "%{$q}%")
                    ->orWhere('ts.nomenclador', 'ilike', "%{$q}%");
            });
        }

        $query->orderBy('tc.codigo')
            ->orderBy('tsc.codigo')
            ->orderBy('ts.servicio_codigo');

        return $query->paginate($perPage);
    }

    public function arbolTarifaBase(): array
    {
        $base = $this->getTarifaBase();

        $cats = DB::table('tarifa_categorias')
            ->where('tarifa_id', (int)$base->id)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre']);

        $subs = DB::table('tarifa_subcategorias')
            ->where('tarifa_id', (int)$base->id)
            ->orderBy('categoria_id')
            ->orderBy('codigo')
            ->get(['id', 'categoria_id', 'codigo', 'nombre']);

        $servs = DB::table('tarifa_servicios')
            ->where('tarifa_id', (int)$base->id)
            ->orderBy('categoria_id')
            ->orderBy('subcategoria_id')
            ->orderBy('servicio_codigo')
            ->get(['id', 'categoria_id', 'subcategoria_id', 'servicio_codigo', 'codigo', 'nomenclador', 'descripcion', 'precio_sin_igv', 'unidad']);

        $subsByCat = [];
        foreach ($subs as $s) {
            $subsByCat[(int)$s->categoria_id][] = $s;
        }

        $servBySub = [];
        foreach ($servs as $sv) {
            $servBySub[(int)$sv->subcategoria_id][] = $sv;
        }

        $tree = [];
        foreach ($cats as $c) {
            $catNode = [
                'id' => (int)$c->id,
                'codigo' => (string)$c->codigo,
                'nombre' => (string)$c->nombre,
                'subcategorias' => [],
            ];

            $subsList = $subsByCat[(int)$c->id] ?? [];
            foreach ($subsList as $s) {
                $subNode = [
                    'id' => (int)$s->id,
                    'codigo' => (string)$s->codigo,
                    'nombre' => (string)$s->nombre,
                    'servicios' => [],
                ];

                $svList = $servBySub[(int)$s->id] ?? [];
                foreach ($svList as $sv) {
                    $subNode['servicios'][] = [
                        'id' => (int)$sv->id,
                        'servicio_codigo' => (string)$sv->servicio_codigo,
                        'codigo' => (string)$sv->codigo,
                        'nomenclador' => $sv->nomenclador !== null ? (string)$sv->nomenclador : null,
                        'descripcion' => (string)$sv->descripcion,
                        'precio_sin_igv' => (string)$sv->precio_sin_igv,
                        'unidad' => (string)$sv->unidad,
                    ];
                }

                $catNode['subcategorias'][] = $subNode;
            }

            $tree[] = $catNode;
        }

        return [
            'tarifa_base' => [
                'id' => (int)$base->id,
                'codigo' => (string)$base->codigo,
                'descripcion_tarifa' => (string)$base->descripcion_tarifa,
            ],
            'tree' => $tree,
        ];
    }
}
