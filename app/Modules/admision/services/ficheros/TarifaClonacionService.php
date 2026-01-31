<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Tarifa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TarifaClonacionService
{
    public function __construct(private AuditService $audit) {}

    public function cloneFromBase(Tarifa $target, array $payload): array
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

        if ($target->tarifa_base) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['No se puede clonar hacia el tarifario base. Seleccione una tarifa operativa.'],
            ]);
        }

        if ($target->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['La tarifa destino debe estar ACTIVA.'],
            ]);
        }

        $cloneAll = (bool)($payload['clone_all'] ?? false);
        $catIdsIn = array_values(array_unique(array_map('intval', $payload['categoria_ids'] ?? [])));
        $subIdsIn = array_values(array_unique(array_map('intval', $payload['subcategoria_ids'] ?? [])));
        $srvIdsIn = array_values(array_unique(array_map('intval', $payload['servicio_ids'] ?? [])));

        return DB::transaction(function () use ($base, $target, $cloneAll, $catIdsIn, $subIdsIn, $srvIdsIn) {
            DB::statement('LOCK TABLE tarifa_categorias IN EXCLUSIVE MODE');
            DB::statement('LOCK TABLE tarifa_subcategorias IN EXCLUSIVE MODE');
            DB::statement('LOCK TABLE tarifa_servicios IN EXCLUSIVE MODE');

            $baseId = (int)$base->id;
            $targetId = (int)$target->id;

            [$baseCatIds, $baseSubIds, $baseSrvIds] = $cloneAll
                ? $this->allBaseIds($baseId)
                : $this->expandSelectionToFullTree($baseId, $catIdsIn, $subIdsIn, $srvIdsIn);

            $baseCats = DB::table('tarifa_categorias')
                ->where('tarifa_id', $baseId)
                ->whereIn('id', $baseCatIds)
                ->orderBy('codigo')
                ->get(['codigo', 'nombre', 'estado']);

            $catRows = [];
            foreach ($baseCats as $c) {
                $catRows[] = [
                    'tarifa_id' => $targetId,
                    'codigo' => (string)$c->codigo,
                    'nombre' => (string)$c->nombre,
                    'estado' => (string)$c->estado,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $this->upsertChunked('tarifa_categorias', $catRows, ['tarifa_id', 'codigo'], ['nombre', 'estado', 'updated_at']);

            $targetCatMap = DB::table('tarifa_categorias')
                ->where('tarifa_id', $targetId)
                ->whereIn('codigo', $baseCats->pluck('codigo')->all())
                ->pluck('id', 'codigo')
                ->map(fn ($v) => (int)$v)
                ->all();

            $baseSubs = DB::table('tarifa_subcategorias AS s')
                ->join('tarifa_categorias AS c', 'c.id', '=', 's.categoria_id')
                ->where('s.tarifa_id', $baseId)
                ->whereIn('s.id', $baseSubIds)
                ->orderBy('c.codigo')
                ->orderBy('s.codigo')
                ->get([
                    'c.codigo AS cat_codigo',
                    's.codigo AS sub_codigo',
                    's.nombre AS sub_nombre',
                    's.estado AS sub_estado',
                ]);

            $subRows = [];
            foreach ($baseSubs as $s) {
                $catCodigo = (string)$s->cat_codigo;
                $catId = $targetCatMap[$catCodigo] ?? null;
                if (!$catId) {
                    continue;
                }

                $subRows[] = [
                    'tarifa_id' => $targetId,
                    'categoria_id' => $catId,
                    'codigo' => (string)$s->sub_codigo,
                    'nombre' => (string)$s->sub_nombre,
                    'estado' => (string)$s->sub_estado,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $this->upsertChunked('tarifa_subcategorias', $subRows, ['categoria_id', 'codigo'], ['nombre', 'estado', 'updated_at']);

            $targetSubRows = DB::table('tarifa_subcategorias AS s')
                ->join('tarifa_categorias AS c', 'c.id', '=', 's.categoria_id')
                ->where('s.tarifa_id', $targetId)
                ->whereIn('c.codigo', $baseCats->pluck('codigo')->all())
                ->get(['s.id', 's.codigo AS sub_codigo', 'c.codigo AS cat_codigo', 's.categoria_id']);

            $targetSubMap = [];
            foreach ($targetSubRows as $r) {
                $key = ((string)$r->cat_codigo) . '|' . ((string)$r->sub_codigo);
                $targetSubMap[$key] = (int)$r->id;
            }

            $existingNomMap = DB::table('tarifa_servicios')
                ->where('tarifa_id', $targetId)
                ->whereNotNull('nomenclador')
                ->pluck('codigo', 'nomenclador')
                ->all();

            $baseServs = DB::table('tarifa_servicios AS s')
                ->join('tarifa_categorias AS c', 'c.id', '=', 's.categoria_id')
                ->join('tarifa_subcategorias AS sc', 'sc.id', '=', 's.subcategoria_id')
                ->where('s.tarifa_id', $baseId)
                ->whereIn('s.id', $baseSrvIds)
                ->orderBy('c.codigo')
                ->orderBy('sc.codigo')
                ->orderBy('s.servicio_codigo')
                ->get([
                    'c.codigo AS cat_codigo',
                    'sc.codigo AS sub_codigo',
                    's.servicio_codigo',
                    's.codigo',
                    's.nomenclador',
                    's.descripcion',
                    's.precio_sin_igv',
                    's.unidad',
                    's.estado',
                ]);

            $nulledNomencladores = 0;
            $svcRows = [];

            foreach ($baseServs as $sv) {
                $catCodigo = (string)$sv->cat_codigo;
                $subCodigo = (string)$sv->sub_codigo;

                $catId = $targetCatMap[$catCodigo] ?? null;
                if (!$catId) {
                    continue;
                }

                $subKey = $catCodigo . '|' . $subCodigo;
                $subId = $targetSubMap[$subKey] ?? null;
                if (!$subId) {
                    continue;
                }

                $nom = $sv->nomenclador !== null ? trim((string)$sv->nomenclador) : null;
                if ($nom === '') {
                    $nom = null;
                }

                if ($nom !== null) {
                    $existingCode = $existingNomMap[$nom] ?? null;
                    if ($existingCode !== null && $existingCode !== (string)$sv->codigo) {
                        $nom = null;
                        $nulledNomencladores++;
                    } else {
                        $existingNomMap[$nom] = (string)$sv->codigo;
                    }
                }

                $svcRows[] = [
                    'tarifa_id' => $targetId,
                    'categoria_id' => $catId,
                    'subcategoria_id' => $subId,
                    'servicio_codigo' => (string)$sv->servicio_codigo,
                    'codigo' => (string)$sv->codigo,
                    'nomenclador' => $nom,
                    'descripcion' => (string)$sv->descripcion,
                    'precio_sin_igv' => $sv->precio_sin_igv,
                    'unidad' => $sv->unidad,
                    'estado' => (string)$sv->estado,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $this->upsertChunked(
                'tarifa_servicios',
                $svcRows,
                ['tarifa_id', 'codigo'],
                ['categoria_id', 'subcategoria_id', 'servicio_codigo', 'nomenclador', 'descripcion', 'precio_sin_igv', 'unidad', 'estado', 'updated_at']
            );

            if ($cloneAll) {
                $baseCatCodes = $baseCats->pluck('codigo')->map(fn ($v) => (string)$v)->all();
                $baseSubKeys = $baseSubs
                    ->map(fn ($s) => ((string)$s->cat_codigo) . '|' . ((string)$s->sub_codigo))
                    ->values()
                    ->all();
                $baseSrvCodes = $baseServs->pluck('codigo')->map(fn ($v) => (string)$v)->all();

                if (count($baseSrvCodes) > 0) {
                    DB::table('tarifa_servicios')
                        ->where('tarifa_id', $targetId)
                        ->whereNotIn('codigo', $baseSrvCodes)
                        ->delete();
                } else {
                    DB::table('tarifa_servicios')->where('tarifa_id', $targetId)->delete();
                }

                if (count($baseSubKeys) > 0) {
                    $allowedSubIds = DB::table('tarifa_subcategorias AS s')
                        ->join('tarifa_categorias AS c', 'c.id', '=', 's.categoria_id')
                        ->where('s.tarifa_id', $targetId)
                        ->whereIn('c.codigo', $baseCatCodes)
                        ->whereIn(DB::raw("c.codigo || '|' || s.codigo"), $baseSubKeys)
                        ->pluck('s.id')
                        ->map(fn ($v) => (int)$v)
                        ->all();

                    if (count($allowedSubIds) > 0) {
                        DB::table('tarifa_subcategorias')
                            ->where('tarifa_id', $targetId)
                            ->whereNotIn('id', $allowedSubIds)
                            ->delete();
                    } else {
                        DB::table('tarifa_subcategorias')->where('tarifa_id', $targetId)->delete();
                    }
                } else {
                    DB::table('tarifa_subcategorias')->where('tarifa_id', $targetId)->delete();
                }

                if (count($baseCatCodes) > 0) {
                    DB::table('tarifa_categorias')
                        ->where('tarifa_id', $targetId)
                        ->whereNotIn('codigo', $baseCatCodes)
                        ->delete();
                } else {
                    DB::table('tarifa_categorias')->where('tarifa_id', $targetId)->delete();
                }
            }

            $result = [
                'base' => ['id' => $baseId, 'codigo' => (string)$base->codigo],
                'target' => ['id' => $targetId, 'codigo' => (string)$target->codigo],
                'selection' => [
                    'clone_all' => $cloneAll,
                    'categorias' => count($baseCatIds),
                    'subcategorias' => count($baseSubIds),
                    'servicios' => count($baseSrvIds),
                ],
                'applied' => [
                    'categorias' => count($catRows),
                    'subcategorias' => count($subRows),
                    'servicios' => count($svcRows),
                    'nomencladores_nulled_por_conflicto' => $nulledNomencladores,
                ],
            ];

            $this->audit->log(
                'tarifa.clone_from_base',
                'Clonar desde tarifa base',
                'Tarifa',
                (string)$targetId,
                [
                    'tarifa_base_id' => $baseId,
                    'tarifa_target_id' => $targetId,
                    'clone_all' => $cloneAll,
                    'selected_counts' => [
                        'categorias' => count($baseCatIds),
                        'subcategorias' => count($baseSubIds),
                        'servicios' => count($baseSrvIds),
                    ],
                    'applied_counts' => [
                        'categorias' => count($catRows),
                        'subcategorias' => count($subRows),
                        'servicios' => count($svcRows),
                    ],
                    'nomencladores_nulled_por_conflicto' => $nulledNomencladores,
                ]
            );                        

            return $result;
        });
    }

    private function allBaseIds(int $baseTarifaId): array
    {
        $catIds = DB::table('tarifa_categorias')->where('tarifa_id', $baseTarifaId)->pluck('id')->map(fn ($v) => (int)$v)->all();
        $subIds = DB::table('tarifa_subcategorias')->where('tarifa_id', $baseTarifaId)->pluck('id')->map(fn ($v) => (int)$v)->all();
        $srvIds = DB::table('tarifa_servicios')->where('tarifa_id', $baseTarifaId)->pluck('id')->map(fn ($v) => (int)$v)->all();

        return [$catIds, $subIds, $srvIds];
    }

    private function expandSelectionToFullTree(int $baseTarifaId, array $catIds, array $subIds, array $srvIds): array
    {
        $catIds = array_values(array_unique(array_map('intval', $catIds)));
        $subIds = array_values(array_unique(array_map('intval', $subIds)));
        $srvIds = array_values(array_unique(array_map('intval', $srvIds)));

        $selectedCatIds = [];
        if (count($catIds) > 0) {
            $selectedCatIds = DB::table('tarifa_categorias')
                ->where('tarifa_id', $baseTarifaId)
                ->whereIn('id', $catIds)
                ->pluck('id')
                ->map(fn ($v) => (int)$v)
                ->all();

            if (count($selectedCatIds) !== count($catIds)) {
                throw ValidationException::withMessages([
                    'categoria_ids' => ['Una o más categorías no pertenecen al tarifario base.'],
                ]);
            }
        }

        $selectedSubRows = collect();
        if (count($subIds) > 0) {
            $selectedSubRows = DB::table('tarifa_subcategorias')
                ->where('tarifa_id', $baseTarifaId)
                ->whereIn('id', $subIds)
                ->get(['id', 'categoria_id']);

            if ($selectedSubRows->count() !== count($subIds)) {
                throw ValidationException::withMessages([
                    'subcategoria_ids' => ['Una o más subcategorías no pertenecen al tarifario base.'],
                ]);
            }
        }

        $selectedSrvRows = collect();
        if (count($srvIds) > 0) {
            $selectedSrvRows = DB::table('tarifa_servicios')
                ->where('tarifa_id', $baseTarifaId)
                ->whereIn('id', $srvIds)
                ->get(['id', 'categoria_id', 'subcategoria_id']);

            if ($selectedSrvRows->count() !== count($srvIds)) {
                throw ValidationException::withMessages([
                    'servicio_ids' => ['Uno o más servicios no pertenecen al tarifario base.'],
                ]);
            }
        }

        if (count($selectedCatIds) === 0 && $selectedSubRows->isEmpty() && $selectedSrvRows->isEmpty()) {
            throw ValidationException::withMessages([
                'selection' => ['Debe seleccionar al menos una categoría, subcategoría o servicio.'],
            ]);
        }

        $catsFromSubs = $selectedSubRows->pluck('categoria_id')->map(fn ($v) => (int)$v)->all();
        $catsFromSrvs = $selectedSrvRows->pluck('categoria_id')->map(fn ($v) => (int)$v)->all();

        $finalCatIds = array_values(array_unique(array_merge($selectedCatIds, $catsFromSubs, $catsFromSrvs)));

        $subIdsDirect = $selectedSubRows->pluck('id')->map(fn ($v) => (int)$v)->all();
        $subIdsFromSrvs = $selectedSrvRows->pluck('subcategoria_id')->map(fn ($v) => (int)$v)->all();

        $subIdsUnderSelectedCats = [];
        if (count($selectedCatIds) > 0) {
            $subIdsUnderSelectedCats = DB::table('tarifa_subcategorias')
                ->where('tarifa_id', $baseTarifaId)
                ->whereIn('categoria_id', $selectedCatIds)
                ->pluck('id')
                ->map(fn ($v) => (int)$v)
                ->all();
        }

        $finalSubIds = array_values(array_unique(array_merge($subIdsDirect, $subIdsFromSrvs, $subIdsUnderSelectedCats)));

        $srvIdsDirect = $selectedSrvRows->pluck('id')->map(fn ($v) => (int)$v)->all();

        $srvIdsFromCats = [];
        if (count($selectedCatIds) > 0) {
            $srvIdsFromCats = DB::table('tarifa_servicios')
                ->where('tarifa_id', $baseTarifaId)
                ->whereIn('categoria_id', $selectedCatIds)
                ->pluck('id')
                ->map(fn ($v) => (int)$v)
                ->all();
        }

        $srvIdsFromSubs = [];
        if (count($subIdsDirect) > 0) {
            $srvIdsFromSubs = DB::table('tarifa_servicios')
                ->where('tarifa_id', $baseTarifaId)
                ->whereIn('subcategoria_id', $subIdsDirect)
                ->pluck('id')
                ->map(fn ($v) => (int)$v)
                ->all();
        }

        $finalSrvIds = array_values(array_unique(array_merge($srvIdsDirect, $srvIdsFromCats, $srvIdsFromSubs)));

        return [$finalCatIds, $finalSubIds, $finalSrvIds];
    }

    private function upsertChunked(string $table, array $rows, array $uniqueBy, array $updateCols): void
    {
        if (count($rows) === 0) {
            return;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy, $updateCols);
        }
    }
}
