<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaCategoria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TarifaCategoriaService
{
    public ?PropagacionResultado $lastPropagationResult = null;

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

    private const INDEX_CACHE_TTL_SECONDS = 30;

    public function paginate(Tarifa $tarifa, array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int)($filters['page'] ?? 1));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $status = isset($filters['status']) ? trim((string)$filters['status']) : null;

        $cacheKey = sprintf('tarifario:cat:index:%s:%s:%s:%s:%s', $tarifa->id, $page, $perPage, $q ?? '', $status ?? '');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($tarifa, $filters, $perPage, $page) {
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

            return $query->orderBy('codigo')->paginate($perPage, ['*'], 'page', $page)->appends([
                'per_page' => $perPage,
                'q' => $q,
                'status' => $status,
            ]);
        });
    }

    private const LOOKUP_CACHE_TTL_SECONDS = 60;

    public function lookup(Tarifa $tarifa, bool $onlyActivas = true): array
    {
        $key = sprintf('tarifario:cat:lookup:%s:%s', $tarifa->id, $onlyActivas ? '1' : '0');

        return Cache::remember($key, self::LOOKUP_CACHE_TTL_SECONDS, function () use ($tarifa, $onlyActivas) {
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
        });
    }

    public function clearLookupCache(Tarifa $tarifa): void
    {
        Cache::forget(sprintf('tarifario:cat:lookup:%s:1', $tarifa->id));
        Cache::forget(sprintf('tarifario:cat:lookup:%s:0', $tarifa->id));
    }

    public function create(Tarifa $tarifa, array $data): TarifaCategoria
    {
        $this->assertTarifaActiva($tarifa);

        $nombre = trim((string)($data['descripcion'] ?? ''));
        if ($nombre === '') {
            throw ValidationException::withMessages(['descripcion' => ['La descripción es requerida.']]);
        }

        $existeMismoNombre = TarifaCategoria::query()
            ->where('tarifa_id', $tarifa->id)
            ->where('nombre', $nombre)
            ->exists();

        if ($existeMismoNombre) {
            throw ValidationException::withMessages([
                'descripcion' => ["Ya existe una categoría con la descripción '{$nombre}' en este tarifario."],
            ]);
        }

        return DB::transaction(function () use ($tarifa, $data) {
            DB::statement('LOCK TABLE tarifa_categorias IN EXCLUSIVE MODE');

            $codigo = $this->peekNextCodigo($tarifa);
            $estado = $data['estado'] ?? RecordStatus::ACTIVO->value;

            $categoria = TarifaCategoria::create([
                'tarifa_id' => $tarifa->id,
                'codigo' => $codigo,
                'nombre' => $data['descripcion'],
                'estado' => $estado,
            ]);

            $this->lastPropagationResult = null;
            if ($tarifa->tarifa_base) {
                $this->lastPropagationResult = $this->propagarCategoriaAOtrasTarifas($codigo, $data['descripcion'], $estado, (int)$tarifa->id);
            }

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

            $this->clearLookupCache($tarifa);
            return $categoria;
        });
    }

    private function propagarCategoriaAOtrasTarifas(string $codigo, string $nombre, string $estado, int $tarifaBaseId): PropagacionResultado
    {
        $result = new PropagacionResultado();

        $otrasTarifas = Tarifa::query()
            ->where('tarifa_base', false)
            ->where('estado', RecordStatus::ACTIVO->value)
            ->where('id', '<>', $tarifaBaseId)
            ->get(['id', 'codigo', 'descripcion_tarifa']);

        if ($otrasTarifas->isEmpty()) {
            return $result;
        }

        $now = now();
        $rows = [];

        foreach ($otrasTarifas as $t) {
            $targetTarifa = Tarifa::query()->find($t->id);
            if (!$targetTarifa) {
                continue;
            }

            $existeMisma = TarifaCategoria::query()
                ->where('tarifa_id', $t->id)
                ->where('codigo', $codigo)
                ->where('nombre', $nombre)
                ->exists();

            if ($existeMisma) {
                $result->omitidos[] = [
                    'tipo' => 'categoria',
                    'tarifa_id' => (int)$t->id,
                    'tarifa_codigo' => (string)$t->codigo,
                    'tarifa_descripcion' => (string)$t->descripcion_tarifa,
                    'mensaje' => "La categoría '{$nombre}' (código {$codigo}) ya existe en este tarifario.",
                ];
                continue;
            }

            $codigoOcupado = TarifaCategoria::query()
                ->where('tarifa_id', $t->id)
                ->where('codigo', $codigo)
                ->exists();

            $codigoUsar = $codigoOcupado ? $this->peekNextCodigo($targetTarifa) : $codigo;

            $rows[] = [
                'tarifa_id' => (int)$t->id,
                'codigo' => $codigoUsar,
                'nombre' => $nombre,
                'estado' => $estado,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($codigoUsar !== $codigo) {
                $result->creadosConCodigoDiferente[] = [
                    'tipo' => 'categoria',
                    'tarifa_id' => (int)$t->id,
                    'tarifa_codigo' => (string)$t->codigo,
                    'tarifa_descripcion' => (string)$t->descripcion_tarifa,
                    'mensaje' => "Categoría '{$nombre}': código {$codigo} ya ocupado; creada con código {$codigoUsar}.",
                    'codigo_base' => $codigo,
                    'codigo_usado' => $codigoUsar,
                ];
            } else {
                $result->creados[] = [
                    'tipo' => 'categoria',
                    'tarifa_id' => (int)$t->id,
                    'tarifa_codigo' => (string)$t->codigo,
                    'tarifa_descripcion' => (string)$t->descripcion_tarifa,
                    'mensaje' => "Categoría creada con código {$codigo}.",
                    'codigo_base' => $codigo,
                    'codigo_usado' => $codigo,
                ];
            }
        }

        if (!empty($rows)) {
            DB::table('tarifa_categorias')->insert($rows);
        }

        return $result;
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
            $categoria->refresh();

            if ($categoria->estado !== RecordStatus::ACTIVO->value) {
                DB::table('tarifa_subcategorias')
                    ->where('tarifa_id', $tarifa->id)
                    ->where('categoria_id', $categoria->id)
                    ->update(['estado' => $categoria->estado, 'updated_at' => now()]);

                DB::table('tarifa_servicios')
                    ->where('tarifa_id', $tarifa->id)
                    ->where('categoria_id', $categoria->id)
                    ->update(['estado' => $categoria->estado, 'updated_at' => now()]);
            } else {
                DB::table('tarifa_subcategorias')
                    ->where('tarifa_id', $tarifa->id)
                    ->where('categoria_id', $categoria->id)
                    ->update(['estado' => RecordStatus::ACTIVO->value, 'updated_at' => now()]);

                DB::table('tarifa_servicios')
                    ->where('tarifa_id', $tarifa->id)
                    ->where('categoria_id', $categoria->id)
                    ->update(['estado' => RecordStatus::ACTIVO->value, 'updated_at' => now()]);
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

            $this->clearLookupCache($tarifa);
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
            $categoria->refresh();

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

            $this->clearLookupCache($tarifa);
            return $categoria;
        });
    }
}

