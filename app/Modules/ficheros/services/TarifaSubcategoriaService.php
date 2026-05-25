<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaCategoria;
use App\Modules\admision\models\TarifaSubcategoria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TarifaSubcategoriaService
{
    use AppliesListingQuery;

    public ?PropagacionResultado $lastPropagationResult = null;

    public function __construct(
        private AuditService $audit,
        private TarifaCategoriaService $categoriaService
    ) {}

    private function assertTarifaActiva(Tarifa $tarifa): void
    {
        if ($tarifa->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['La tarifa seleccionada debe estar activa para gestionar subcategorías.'],
            ]);
        }
    }

    private function assertBelongsTarifa(Tarifa $tarifa, TarifaSubcategoria $sub): void
    {
        if ((int)$sub->tarifa_id !== (int)$tarifa->id) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['La subcategoría seleccionada no pertenece a la tarifa indicada. Actualiza la pantalla e intenta otra vez.'],
            ]);
        }
    }

    private function formatCodigo(int $n): string
    {
        return CodigoCorrelativo::format($n);
    }

    private function findCategoriaActiva(Tarifa $tarifa, int $categoriaId): TarifaCategoria
    {
        $cat = TarifaCategoria::query()
            ->where('tarifa_id', $tarifa->id)
            ->where('id', $categoriaId)
            ->first();

        if (!$cat) {
            throw ValidationException::withMessages(['categoria_id' => ['La categoría seleccionada no existe en esta tarifa.']]);
        }

        if ($cat->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['categoria_id' => ['La categoría seleccionada debe estar activa para crear subcategorías.']]);
        }

        return $cat;
    }

    public function peekNextCodigo(Tarifa $tarifa, int $categoriaId): string
    {
        if ($categoriaId < 1) {
            throw ValidationException::withMessages(['categoria_id' => ['Selecciona una categoría para generar el código de subcategoría.']]);
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

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const INDEX_CACHE_VERSION_PREFIX = 'tarifario:sub:index:version:';

    public static function invalidateIndexCacheForTarifa(int $tarifaId): void
    {
        $key = self::INDEX_CACHE_VERSION_PREFIX . $tarifaId;
        Cache::put($key, (int) Cache::get($key, 0) + 1, 86400);
    }

    public static function invalidateLookupCacheForCategoria(int $tarifaId, int $categoriaId): void
    {
        Cache::forget(sprintf('tarifario:sub:lookup:%s:%s:1', $tarifaId, $categoriaId));
        Cache::forget(sprintf('tarifario:sub:lookup:%s:%s:0', $tarifaId, $categoriaId));
    }

    private function indexCacheVersion(Tarifa $tarifa): int
    {
        return (int) Cache::get(self::INDEX_CACHE_VERSION_PREFIX . $tarifa->id, 0);
    }

    public function paginate(Tarifa $tarifa, GridParams $params): LengthAwarePaginator
    {
        $cacheKey = 'tarifario:sub:index:' . $this->indexCacheVersion($tarifa) . ':' . $tarifa->id . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($tarifa, $params) {
            $categoriaId = (int) ($params->filter('categoria_id') ?? 0);

            $query = TarifaSubcategoria::query()->where('tarifa_id', $tarifa->id);

            if ($categoriaId > 0) {
                $query->where('categoria_id', $categoriaId);
            }

            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'nombre']);
            $this->applyListingSort($query, $params, ['codigo', 'nombre', 'estado', 'categoria_id'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    private const LOOKUP_CACHE_TTL_SECONDS = 60;

    private function invalidateLookupCache(Tarifa $tarifa, int $categoriaId): void
    {
        self::invalidateLookupCacheForCategoria((int) $tarifa->id, $categoriaId);
    }

    private function invalidateTarifarioCaches(Tarifa $tarifa, int $categoriaId): void
    {
        self::invalidateIndexCacheForTarifa((int) $tarifa->id);
        TarifaServicioService::invalidateIndexCacheForTarifa((int) $tarifa->id);
        TarifarioCatalogoService::invalidateServiciosCacheForTarifa((int) $tarifa->id);
        $this->invalidateLookupCache($tarifa, $categoriaId);
    }

    public function lookup(Tarifa $tarifa, int $categoriaId, bool $onlyActivas = true): array
    {
        if ($categoriaId < 1) {
            return [];
        }

        $key = sprintf('tarifario:sub:lookup:%s:%s:%s', $tarifa->id, $categoriaId, $onlyActivas ? '1' : '0');

        return Cache::remember($key, self::LOOKUP_CACHE_TTL_SECONDS, function () use ($tarifa, $categoriaId, $onlyActivas) {
            $q = CodigoCorrelativo::orderByCodigoAsc(
                TarifaSubcategoria::query()
                    ->where('tarifa_id', $tarifa->id)
                    ->where('categoria_id', $categoriaId)
                    ->when($onlyActivas, fn ($x) => $x->where('estado', RecordStatus::ACTIVO->value))
            )->get(['id', 'codigo', 'nombre', 'estado']);

            return $q->map(fn($s) => [
                'id' => (int)$s->id,
                'codigo' => (string)$s->codigo,
                'descripcion' => (string)$s->nombre,
                'estado' => (string)$s->estado,
            ])->all();
        });
    }


    public function create(Tarifa $tarifa, array $data): TarifaSubcategoria
    {
        $this->assertTarifaActiva($tarifa);

        return DB::transaction(function () use ($tarifa, $data) {
            DB::statement('LOCK TABLE tarifa_subcategorias IN EXCLUSIVE MODE');

            $categoriaId = (int)$data['categoria_id'];
            $cat = $this->findCategoriaActiva($tarifa, $categoriaId);

            $codigo = $this->peekNextCodigo($tarifa, $cat->id);

            $estado = $data['estado'] ?? RecordStatus::ACTIVO->value;

            $sub = TarifaSubcategoria::create([
                'tarifa_id' => $tarifa->id,
                'categoria_id' => $cat->id,
                'codigo' => $codigo,
                'nombre' => $data['descripcion'],
                'estado' => $estado,
            ]);

            $this->lastPropagationResult = null;
            if ($tarifa->tarifa_base) {
                $this->lastPropagationResult = $this->propagarSubcategoriaAOtrasTarifas(
                    (string)$cat->codigo,
                    (string)$cat->nombre,
                    $codigo,
                    $data['descripcion'],
                    $estado,
                    (int)$tarifa->id
                );
            }

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

            $this->invalidateTarifarioCaches($tarifa, (int) $cat->id);
            return $sub;
        });
    }

    private function propagarSubcategoriaAOtrasTarifas(
        string $catCodigo,
        string $catNombre,
        string $subCodigo,
        string $subNombre,
        string $estado,
        int $tarifaBaseId
    ): PropagacionResultado {
        $result = new PropagacionResultado();

        $otrasTarifas = Tarifa::query()
            ->where('tarifa_base', false)
            ->where('estado', RecordStatus::ACTIVO->value)
            ->where('id', '<>', $tarifaBaseId)
            ->get(['id', 'codigo', 'descripcion_tarifa']);

        if ($otrasTarifas->isEmpty()) {
            return $result;
        }

        foreach ($otrasTarifas as $t) {
            $targetTarifa = Tarifa::query()->find($t->id);
            if (!$targetTarifa) {
                continue;
            }

            $cat = TarifaCategoria::query()
                ->where('tarifa_id', $t->id)
                ->where('nombre', $catNombre)
                ->first();

            if (!$cat) {
                $codigoCatOcupado = TarifaCategoria::query()
                    ->where('tarifa_id', $t->id)
                    ->where('codigo', $catCodigo)
                    ->exists();

                $codigoCatUsar = $codigoCatOcupado
                    ? $this->categoriaService->peekNextCodigo($targetTarifa)
                    : $catCodigo;

                $cat = TarifaCategoria::create([
                    'tarifa_id' => $t->id,
                    'codigo' => $codigoCatUsar,
                    'nombre' => $catNombre,
                    'estado' => $estado,
                ]);
            }

            $codigoCat = (string)$cat->codigo;

            $existeMisma = TarifaSubcategoria::query()
                ->where('tarifa_id', $t->id)
                ->where('categoria_id', $cat->id)
                ->where('codigo', $subCodigo)
                ->where('nombre', $subNombre)
                ->exists();

            if ($existeMisma) {
                $result->omitidos[] = [
                    'tipo' => 'subcategoria',
                    'tarifa_id' => (int)$t->id,
                    'tarifa_codigo' => (string)$t->codigo,
                    'tarifa_descripcion' => (string)$t->descripcion_tarifa,
                    'mensaje' => "La subcategoría '{$subNombre}' ({$codigoCat}.{$subCodigo}) ya existe.",
                ];
                continue;
            }

            $codigoSubOcupado = TarifaSubcategoria::query()
                ->where('tarifa_id', $t->id)
                ->where('categoria_id', $cat->id)
                ->where('codigo', $subCodigo)
                ->exists();

            $codigoSubUsar = $codigoSubOcupado
                ? $this->peekNextCodigo($targetTarifa, (int)$cat->id)
                : $subCodigo;

            $item = [
                'tipo' => 'subcategoria',
                'tarifa_id' => (int)$t->id,
                'tarifa_codigo' => (string)$t->codigo,
                'tarifa_descripcion' => (string)$t->descripcion_tarifa,
                'mensaje' => $codigoSubUsar !== $subCodigo
                    ? "Subcategoría '{$subNombre}': código {$codigoCat}.{$subCodigo} ocupado; creada con {$codigoCat}.{$codigoSubUsar}."
                    : "Subcategoría creada con código {$codigoCat}.{$codigoSubUsar}.",
                'codigo_base' => "{$catCodigo}.{$subCodigo}",
                'codigo_usado' => "{$codigoCat}.{$codigoSubUsar}",
            ];
            if ($codigoSubUsar !== $subCodigo) {
                $result->creadosConCodigoDiferente[] = $item;
            } else {
                $result->creados[] = $item;
            }

            TarifaSubcategoria::create([
                'tarifa_id' => $t->id,
                'categoria_id' => $cat->id,
                'codigo' => $codigoSubUsar,
                'nombre' => $subNombre,
                'estado' => $estado,
            ]);

            $targetTarifaId = (int) $t->id;
            $targetCategoriaId = (int) $cat->id;
            TarifaCategoriaService::invalidateIndexCacheForTarifa($targetTarifaId);
            TarifaCategoriaService::clearLookupCacheForTarifa($targetTarifaId);
            self::invalidateIndexCacheForTarifa($targetTarifaId);
            self::invalidateLookupCacheForCategoria($targetTarifaId, $targetCategoriaId);
            TarifaServicioService::invalidateIndexCacheForTarifa($targetTarifaId);
            TarifarioCatalogoService::invalidateServiciosCacheForTarifa($targetTarifaId);
        }

        return $result;
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
            $sub->refresh();

            if ($sub->estado !== RecordStatus::ACTIVO->value) {
                DB::table('tarifa_servicios')
                    ->where('tarifa_id', $tarifa->id)
                    ->where('subcategoria_id', $sub->id)
                    ->update(['estado' => $sub->estado, 'updated_at' => now()]);
            } else {
                DB::table('tarifa_servicios')
                    ->where('tarifa_id', $tarifa->id)
                    ->where('subcategoria_id', $sub->id)
                    ->update(['estado' => RecordStatus::ACTIVO->value, 'updated_at' => now()]);
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

            $this->invalidateTarifarioCaches($tarifa, (int) $sub->categoria_id);
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
            $sub->refresh();

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

            $this->invalidateTarifarioCaches($tarifa, (int) $sub->categoria_id);
            return $sub;
        });
    }
}

