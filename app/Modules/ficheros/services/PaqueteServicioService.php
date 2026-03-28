<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Paquete;
use App\Modules\admision\models\Tarifa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaqueteServicioService
{
    public function __construct(private AuditService $audit) {}

    public function listPaquetesPorTarifa(Tarifa $tarifa): Collection
    {
        return Paquete::query()
            ->where('tarifa_id', $tarifa->id)
            ->where('estado', RecordStatus::ACTIVO->value)
            ->orderByRaw('CAST(codigo AS INTEGER) ASC')
            ->get(['id', 'codigo', 'descripcion', 'tarifa_id', 'estado']);
    }

    public function arbolServiciosPorTarifa(Tarifa $tarifa): array
    {
        $cats = DB::table('tarifa_categorias')
            ->where('tarifa_id', (int) $tarifa->id)
            ->where('estado', RecordStatus::ACTIVO->value)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre']);

        $subs = DB::table('tarifa_subcategorias')
            ->where('tarifa_id', (int) $tarifa->id)
            ->where('estado', RecordStatus::ACTIVO->value)
            ->orderBy('categoria_id')
            ->orderBy('codigo')
            ->get(['id', 'categoria_id', 'codigo', 'nombre']);

        $servs = DB::table('tarifa_servicios')
            ->where('tarifa_id', (int) $tarifa->id)
            ->where('estado', RecordStatus::ACTIVO->value)
            ->orderBy('categoria_id')
            ->orderBy('subcategoria_id')
            ->orderBy('servicio_codigo')
            ->get(['id', 'categoria_id', 'subcategoria_id', 'codigo', 'descripcion', 'precio_sin_igv', 'unidad']);

        $subsByCat = [];
        foreach ($subs as $s) {
            $subsByCat[(int) $s->categoria_id][] = $s;
        }

        $servBySub = [];
        foreach ($servs as $sv) {
            $servBySub[(int) $sv->subcategoria_id][] = $sv;
        }

        $tree = [];
        foreach ($cats as $c) {
            $catNode = [
                'id' => (int) $c->id,
                'codigo' => (string) $c->codigo,
                'nombre' => (string) $c->nombre,
                'subcategorias' => [],
            ];

            $subsList = $subsByCat[(int) $c->id] ?? [];
            foreach ($subsList as $s) {
                $subNode = [
                    'id' => (int) $s->id,
                    'codigo' => (string) $s->codigo,
                    'nombre' => (string) $s->nombre,
                    'servicios' => [],
                ];

                $svList = $servBySub[(int) $s->id] ?? [];
                foreach ($svList as $sv) {
                    $subNode['servicios'][] = [
                        'id' => (int) $sv->id,
                        'codigo' => (string) $sv->codigo,
                        'descripcion' => (string) $sv->descripcion,
                        'precio_sin_igv' => (string) $sv->precio_sin_igv,
                        'unidad' => (string) $sv->unidad,
                    ];
                }

                $catNode['subcategorias'][] = $subNode;
            }

            $tree[] = $catNode;
        }

        return [
            'tarifa' => [
                'id' => (int) $tarifa->id,
                'codigo' => (string) $tarifa->codigo,
                'descripcion_tarifa' => (string) $tarifa->descripcion_tarifa,
            ],
            'tree' => $tree,
        ];
    }

    public function listServiciosPaquete(Paquete $paquete): Collection
    {
        return DB::table('paquete_servicios as ps')
            ->join('tarifa_servicios as ts', 'ts.id', '=', 'ps.tarifa_servicio_id')
            ->join('tarifa_categorias as tc', 'tc.id', '=', 'ts.categoria_id')
            ->join('tarifa_subcategorias as tsc', 'tsc.id', '=', 'ts.subcategoria_id')
            ->where('ps.paquete_id', (int) $paquete->id)
            ->orderBy('tc.codigo')
            ->orderBy('tsc.codigo')
            ->orderBy('ts.servicio_codigo')
            ->get([
                'ts.id',
                'ts.codigo',
                'ts.descripcion',
                'ts.precio_sin_igv',
                'ts.unidad',
                'tc.codigo as categoria_codigo',
                'tc.nombre as categoria_nombre',
                'tsc.codigo as subcategoria_codigo',
                'tsc.nombre as subcategoria_nombre',
            ]);
    }

    public function syncServicios(Paquete $paquete, array $servicioIds): array
    {
        $servicioIds = array_values(array_unique(array_map(static fn ($x) => (int) $x, $servicioIds)));

        $valid = DB::table('tarifa_servicios')
            ->where('tarifa_id', (int) $paquete->tarifa_id)
            ->whereIn('id', $servicioIds)
            ->pluck('id')
            ->map(static fn ($x) => (int) $x)
            ->all();

        sort($valid);
        $inputSorted = $servicioIds;
        sort($inputSorted);

        if ($valid !== $inputSorted) {
            throw ValidationException::withMessages([
                'servicio_ids' => ['Uno o más servicios no pertenecen a la tarifa del paquete.'],
            ]);
        }

        return DB::transaction(function () use ($paquete, $valid) {
            $before = $paquete->servicios()->pluck('tarifa_servicios.id')->map(static fn ($x) => (int) $x)->all();
            sort($before);

            $paquete->servicios()->sync($valid);

            $after = $paquete->servicios()->pluck('tarifa_servicios.id')->map(static fn ($x) => (int) $x)->all();
            sort($after);

            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));

            $this->audit->log(
                'masterdata.ficheros.paquetes.servicios.sync',
                'Sincronizar servicios de paquete',
                'paquete',
                (string) $paquete->id,
                [
                    'before' => $before,
                    'after' => $after,
                    'added' => $added,
                    'removed' => $removed,
                ],
                'success',
                200
            );

            return [
                'added' => count($added),
                'removed' => count($removed),
                'total' => count($after),
            ];
        });
    }
}
