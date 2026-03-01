<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\support\RecordStatus;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaRecargoNoche;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TarifarioCatalogoService
{

    private const GRUPO_TO_FACTOR = [
        '704101' => 'factor_clinica',
        '704102' => 'factor_laboratorio',
        '704103' => 'factor_ecografia',
        '704104' => 'factor_procedimientos',
        '704105' => 'factor_rayos_x',
        '704106' => 'factor_tomografia',
        '704107' => 'factor_patologia',
        '704108' => 'factor_medicina_fisica',
        '704109' => 'factor_resonancia',
        '704110' => 'factor_honorarios_medicos',
        '704111' => 'factor_medicinas',
        '704112' => 'factor_equipos_oxigeno',
        '704113' => 'factor_banco_sangre',
        '704114' => 'factor_mamografia',
        '704115' => 'factor_densitometria',
        '704116' => 'factor_psicoprofilaxis',
        '704117' => 'factor_medicamentos_comerciales',
        '704118' => 'factor_medicamentos_genericos',
        '704119' => 'factor_material_medico',
    ];

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

    public function listTarifasParaGestionTarifario(array $filters = []): Collection
    {
        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;

        $query = Tarifa::query()
            ->select(['id', 'codigo', 'descripcion_tarifa', 'iafa_id', 'estado', 'tarifa_base'])
            ->where('estado', RecordStatus::ACTIVO->value);

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('descripcion_tarifa', 'ilike', "%{$q}%");
            });
        }

        return $query
            ->orderByRaw('tarifa_base DESC')
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

    private function normalizarHoraParaRecargo(string $hora): ?string
    {
        $h = trim($hora);
        if ($h === '') {
            return null;
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $h, $m)) {
            $hh = (int)$m[1];
            $mm = (int)$m[2];
            $ss = isset($m[3]) ? (int)$m[3] : 0;
            if ($hh < 0 || $hh > 23 || $mm < 0 || $mm > 59 || $ss < 0 || $ss > 59) {
                return null;
            }
            return sprintf('%02d:%02d:%02d', $hh, $mm, $ss);
        }
        if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', $h, $m)) {
            $hh = (int)$m[1];
            $mm = (int)$m[2];
            $ss = isset($m[3]) ? (int)$m[3] : 0;
            if ($hh < 0 || $hh > 23 || $mm < 0 || $mm > 59 || $ss < 0 || $ss > 59) {
                return null;
            }
            return sprintf('%02d:%02d:%02d', $hh, $mm, $ss);
        }
        return null;
    }

    private function horaEnRangoRecargo(Carbon $horaRef, Carbon $desde, Carbon $hasta): bool
    {
        $refMinutos = $horaRef->hour * 60 + $horaRef->minute;
        $desdeMinutos = $desde->hour * 60 + $desde->minute;
        $hastaMinutos = $hasta->hour * 60 + $hasta->minute;

        if ($desdeMinutos <= $hastaMinutos) {
            return $refMinutos >= $desdeMinutos && $refMinutos < $hastaMinutos;
        }
        return $refMinutos >= $desdeMinutos || $refMinutos < $hastaMinutos;
    }

    private function getFactorForGrupo(Tarifa $tarifa, ?string $grupoCodigo): float
    {
        $key = $grupoCodigo !== null && $grupoCodigo !== '' ? trim($grupoCodigo) : null;
        $factorAttr = $key !== null && isset(self::GRUPO_TO_FACTOR[$key])
            ? self::GRUPO_TO_FACTOR[$key]
            : 'factor_otros_servicios';
        $value = $tarifa->getAttribute($factorAttr);
        return is_numeric($value) ? (float)$value : (float)($tarifa->factor_otros_servicios ?? 1);
    }

    public function paginateServicios(Tarifa $tarifa, array $filters): LengthAwarePaginator
    {
        if ($tarifa->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages([
                'tarifa_id' => ['La tarifa debe estar ACTIVA.'],
            ]);
        }

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
                'ts.grupo_codigo',
                'ts.grupo_descripcion',
                'ts.grupo_abrev',
                'ts.desea_liberar_precio',
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
            $qCompact = str_replace('.', '', $q);
            $query->where(function ($sub) use ($q, $qCompact) {
                $sub->where('ts.codigo', 'ilike', "%{$q}%")
                    ->orWhere('ts.descripcion', 'ilike', "%{$q}%")
                    ->orWhere(function ($n) use ($q, $qCompact) {
                        $n->whereNotNull('ts.nomenclador')
                            ->where(function ($nq) use ($q, $qCompact) {
                                $nq->where('ts.nomenclador', 'ilike', "%{$q}%");
                                if ($qCompact !== $q && $qCompact !== '') {
                                    $nq->orWhere('ts.nomenclador', 'ilike', "%{$qCompact}%");
                                }
                            });
                    });
            });
        }

        $query->orderBy('tc.codigo')
            ->orderBy('tsc.codigo')
            ->orderBy('ts.servicio_codigo');

        $paginator = $query->paginate($perPage);
        $horaStr = isset($filters['hora']) && is_string($filters['hora']) ? trim($filters['hora']) : null;
        $reglasPorCategoria = [];

        if ($horaStr !== null && $horaStr !== '') {
            $horaInput = $this->normalizarHoraParaRecargo($horaStr);
            $horaRef = $horaInput !== null ? Carbon::createFromFormat('H:i:s', $horaInput) : null;
            if ($horaRef && Schema::hasTable('tarifa_recargo_noche')) {
                try {
                    $rules = TarifaRecargoNoche::query()
                        ->where('tarifa_id', (int)$tarifa->id)
                        ->where('estado', \App\Core\support\RecordStatus::ACTIVO->value)
                        ->get();
                    foreach ($rules as $r) {
                        $desde = Carbon::parse($r->hora_desde);
                        $hasta = $r->hora_hasta !== null && $r->hora_hasta !== ''
                            ? Carbon::parse($r->hora_hasta)
                            : Carbon::parse($r->hora_desde)->copy()->addHours(12);
                        $activo = $this->horaEnRangoRecargo($horaRef, $desde, $hasta);
                        if ($activo) {
                            $reglasPorCategoria[(int)$r->tarifa_categoria_id] = (float)$r->porcentaje;
                        }
                    }
                } catch (\Throwable $e) {
                    $reglasPorCategoria = [];
                }
            }
        }

        $esPrecioDirecto = (bool)$tarifa->es_precio_directo;

        $items = collect($paginator->items())->map(function ($row) use ($reglasPorCategoria, $tarifa, $esPrecioDirecto) {
            $arr = (array)$row;
            $catId = (int)($arr['categoria_id'] ?? 0);
            $activo = isset($reglasPorCategoria[$catId]);
            $arr['recargo_noche_activo'] = $activo;
            $arr['recargo_noche_porcentaje'] = $activo ? $reglasPorCategoria[$catId] : 0;

            if (!$esPrecioDirecto) {
                $unidad = (float)($arr['unidad'] ?? 0);
                $factor = $this->getFactorForGrupo($tarifa, isset($arr['grupo_codigo']) ? (string)$arr['grupo_codigo'] : null);
                $arr['precio_sin_igv'] = (string)round($unidad * $factor, 3);
            }

            return (object)$arr;
        });

        $paginator = new LengthAwarePaginatorConcrete(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => $paginator->path()]
        );

        return $paginator;
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
            ->get(['id', 'categoria_id', 'subcategoria_id', 'servicio_codigo', 'codigo', 'nomenclador', 'descripcion', 'precio_sin_igv', 'unidad', 'grupo_codigo', 'grupo_descripcion', 'grupo_abrev']);

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
                        'grupo_codigo' => $sv->grupo_codigo ?? null,
                        'grupo_descripcion' => $sv->grupo_descripcion ?? null,
                        'grupo_abrev' => $sv->grupo_abrev ?? null,
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
