<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\GrupoServicio;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaCategoria;
use App\Modules\admision\models\TarifaServicio;
use App\Modules\admision\models\TarifaSubcategoria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TarifaServicioService
{
    public ?PropagacionResultado $lastPropagationResult = null;

    public function __construct(
        private AuditService $audit,
        private TarifaCategoriaService $categoriaService,
        private TarifaSubcategoriaService $subcategoriaService
    ) {}

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

    private function resolveGrupo(?string $codigo): array
    {
        if ($codigo === null || trim($codigo) === '') {
            return ['grupo_codigo' => null, 'grupo_descripcion' => null, 'grupo_abrev' => null];
        }
        $g = GrupoServicio::activos()->where('codigo', trim($codigo))->first();
        if (!$g) {
            return ['grupo_codigo' => null, 'grupo_descripcion' => null, 'grupo_abrev' => null];
        }
        return [
            'grupo_codigo' => $g->codigo,
            'grupo_descripcion' => $g->descripcion,
            'grupo_abrev' => $g->abrev,
        ];
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

    private const INDEX_CACHE_TTL_SECONDS = 30;

    public function paginate(Tarifa $tarifa, array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int)($filters['page'] ?? 1));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $status = isset($filters['status']) ? trim((string)$filters['status']) : null;
        $categoriaId = isset($filters['categoria_id']) ? (int)$filters['categoria_id'] : 0;
        $subcategoriaId = isset($filters['subcategoria_id']) ? (int)$filters['subcategoria_id'] : 0;
        $grupoCodigo = isset($filters['grupo_codigo']) ? trim((string)$filters['grupo_codigo']) : null;

        $cacheKey = sprintf('tarifario:svc:index:%s:%s:%s:%s:%s:%s:%s:%s', $tarifa->id, $page, $perPage, $q ?? '', $status ?? '', $categoriaId, $subcategoriaId, $grupoCodigo ?? '');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($tarifa, $filters, $perPage, $page) {
            $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
            $status = isset($filters['status']) ? trim((string)$filters['status']) : null;
            $categoriaId = isset($filters['categoria_id']) ? (int)$filters['categoria_id'] : 0;
            $subcategoriaId = isset($filters['subcategoria_id']) ? (int)$filters['subcategoria_id'] : 0;
            $grupoCodigo = isset($filters['grupo_codigo']) ? trim((string)$filters['grupo_codigo']) : null;

            $query = TarifaServicio::query()->where('tarifa_id', $tarifa->id);

            if ($categoriaId > 0) $query->where('categoria_id', $categoriaId);
            if ($subcategoriaId > 0) $query->where('subcategoria_id', $subcategoriaId);
            if ($grupoCodigo !== null && $grupoCodigo !== '') {
                $query->where('grupo_codigo', $grupoCodigo);
            }

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

            return $query->orderBy('codigo')->paginate($perPage, ['*'], 'page', $page)->appends([
                'per_page' => $perPage,
                'q' => $q,
                'status' => $status,
                'categoria_id' => $categoriaId,
                'subcategoria_id' => $subcategoriaId,
                'grupo_codigo' => $grupoCodigo,
            ]);
        });
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

            $grupo = $this->resolveGrupo($data['grupo_codigo'] ?? null);
            $estado = $data['estado'] ?? RecordStatus::ACTIVO->value;
            $deseaLiberarPrecio = (bool)($data['desea_liberar_precio'] ?? false);

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
                'grupo_codigo' => $grupo['grupo_codigo'],
                'grupo_descripcion' => $grupo['grupo_descripcion'],
                'grupo_abrev' => $grupo['grupo_abrev'],
                'desea_liberar_precio' => $deseaLiberarPrecio,
                'estado' => $estado,
            ]);

            $this->lastPropagationResult = null;
            if ($tarifa->tarifa_base) {
                $this->lastPropagationResult = $this->propagarServicioAOtrasTarifas(
                    (string)$cat->codigo,
                    (string)$cat->nombre,
                    (string)$sub->codigo,
                    (string)$sub->nombre,
                    $next['servicio_codigo'],
                    $next['codigo'],
                    $data['descripcion'],
                    $data['precio_sin_igv'],
                    $data['unidad'],
                    $grupo,
                    $nom,
                    $estado,
                    $deseaLiberarPrecio,
                    (int)$tarifa->id
                );
            }

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

    private function propagarServicioAOtrasTarifas(
        string $catCodigo,
        string $catNombre,
        string $subCodigo,
        string $subNombre,
        string $servicioCodigo,
        string $codigoFull,
        string $descripcion,
        float $precioSinIgv,
        float $unidad,
        array $grupo,
        ?string $nomenclador,
        string $estado,
        bool $deseaLiberarPrecio,
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

            $sub = TarifaSubcategoria::query()
                ->where('tarifa_id', $t->id)
                ->where('categoria_id', $cat->id)
                ->where('nombre', $subNombre)
                ->first();

            if (!$sub) {
                $codigoSubOcupado = TarifaSubcategoria::query()
                    ->where('tarifa_id', $t->id)
                    ->where('categoria_id', $cat->id)
                    ->where('codigo', $subCodigo)
                    ->exists();

                $codigoSubUsar = $codigoSubOcupado
                    ? $this->subcategoriaService->peekNextCodigo($targetTarifa, (int)$cat->id)
                    : $subCodigo;

                $sub = TarifaSubcategoria::create([
                    'tarifa_id' => $t->id,
                    'categoria_id' => $cat->id,
                    'codigo' => $codigoSubUsar,
                    'nombre' => $subNombre,
                    'estado' => $estado,
                ]);
            }

            $existeMismo = TarifaServicio::query()
                ->where('tarifa_id', $t->id)
                ->where('codigo', $codigoFull)
                ->where('descripcion', $descripcion)
                ->exists();

            if ($existeMismo) {
                $result->omitidos[] = [
                    'tipo' => 'servicio',
                    'tarifa_id' => (int)$t->id,
                    'tarifa_codigo' => (string)$t->codigo,
                    'tarifa_descripcion' => (string)$t->descripcion_tarifa,
                    'mensaje' => "El servicio '{$descripcion}' (código {$codigoFull}) ya existe.",
                ];
                continue;
            }

            $codigoOcupado = TarifaServicio::query()
                ->where('tarifa_id', $t->id)
                ->where('codigo', $codigoFull)
                ->exists();

            $next = $codigoOcupado
                ? $this->peekNextCodigo($targetTarifa, (int)$cat->id, (int)$sub->id)
                : [
                    'servicio_codigo' => explode('.', $codigoFull)[2] ?? $codigoFull,
                    'codigo' => $codigoFull,
                ];

            $item = [
                'tipo' => 'servicio',
                'tarifa_id' => (int)$t->id,
                'tarifa_codigo' => (string)$t->codigo,
                'tarifa_descripcion' => (string)$t->descripcion_tarifa,
                'mensaje' => $next['codigo'] !== $codigoFull
                    ? "Servicio '{$descripcion}': código {$codigoFull} ocupado; creado con {$next['codigo']}."
                    : "Servicio creado con código {$next['codigo']}.",
                'codigo_base' => $codigoFull,
                'codigo_usado' => $next['codigo'],
            ];
            if ($next['codigo'] !== $codigoFull) {
                $result->creadosConCodigoDiferente[] = $item;
            } else {
                $result->creados[] = $item;
            }

            $nom = $nomenclador;
            if ($nom !== null && trim($nom) !== '') {
                $conflicto = TarifaServicio::query()
                    ->where('tarifa_id', $t->id)
                    ->where('nomenclador', $nom)
                    ->exists();
                if ($conflicto) {
                    $nom = null;
                }
            }

            TarifaServicio::create([
                'tarifa_id' => $t->id,
                'categoria_id' => $cat->id,
                'subcategoria_id' => $sub->id,
                'servicio_codigo' => $next['servicio_codigo'],
                'codigo' => $next['codigo'],
                'nomenclador' => $nom,
                'descripcion' => $descripcion,
                'precio_sin_igv' => $precioSinIgv,
                'unidad' => $unidad,
                'grupo_codigo' => $grupo['grupo_codigo'],
                'grupo_descripcion' => $grupo['grupo_descripcion'],
                'grupo_abrev' => $grupo['grupo_abrev'],
                'desea_liberar_precio' => $deseaLiberarPrecio,
                'estado' => $estado,
            ]);
        }

        return $result;
    }

    public function update(Tarifa $tarifa, TarifaServicio $srv, array $data): TarifaServicio
    {
        $this->assertTarifaActiva($tarifa);
        $this->assertBelongs($tarifa, $srv);

        return DB::transaction(function () use ($tarifa, $srv, $data) {
            $before = $srv->only(['nomenclador', 'descripcion', 'precio_sin_igv', 'unidad', 'grupo_codigo', 'grupo_descripcion', 'grupo_abrev', 'desea_liberar_precio', 'estado']);

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

            $grupo = $this->resolveGrupo($data['grupo_codigo'] ?? null);

            $deseaLiberarPrecio = array_key_exists('desea_liberar_precio', $data)
                ? (bool)$data['desea_liberar_precio']
                : $srv->desea_liberar_precio;

            $srv->fill([
                'nomenclador' => $nom,
                'descripcion' => $data['descripcion'],
                'precio_sin_igv' => $data['precio_sin_igv'],
                'unidad' => $data['unidad'],
                'grupo_codigo' => $grupo['grupo_codigo'],
                'grupo_descripcion' => $grupo['grupo_descripcion'],
                'grupo_abrev' => $grupo['grupo_abrev'],
                'desea_liberar_precio' => $deseaLiberarPrecio,
                'estado' => $data['estado'],
            ]);
            $srv->save();
            $srv->refresh();

            $after = $srv->only(['nomenclador', 'descripcion', 'precio_sin_igv', 'unidad', 'grupo_codigo', 'grupo_descripcion', 'grupo_abrev', 'desea_liberar_precio', 'estado']);

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

