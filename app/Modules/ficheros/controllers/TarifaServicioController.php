<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\ParametroSistema;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaServicio;
use App\Modules\ficheros\requests\TarifaServicioStoreRequest;
use App\Modules\ficheros\requests\TarifaServicioUpdateRequest;
use App\Modules\ficheros\services\TarifaServicioService;
use App\Modules\ficheros\support\TarifaServicioPrecioIgv;
use Illuminate\Http\Request;

class TarifaServicioController extends Controller
{
    public function __construct(private TarifaServicioService $service) {}

    private function present(TarifaServicio $s, float $igvPorcentaje): array
    {
        return [
            'id' => (int)$s->id,
            'tarifa_id' => (int)$s->tarifa_id,
            'categoria_id' => (int)$s->categoria_id,
            'subcategoria_id' => (int)$s->subcategoria_id,
            'servicio_codigo' => (string)$s->servicio_codigo,
            'codigo' => (string)$s->codigo,
            'nomenclador' => $s->nomenclador,
            'descripcion' => (string)$s->descripcion,
            'precio_sin_igv' => $s->precio_sin_igv,
            'precio_con_igv' => TarifaServicioPrecioIgv::precioConIgvDesdeSin($s->precio_sin_igv, $igvPorcentaje),
            'unidad' => $s->unidad,
            'grupo_codigo' => $s->grupo_codigo,
            'grupo_descripcion' => $s->grupo_descripcion,
            'grupo_abrev' => $s->grupo_abrev,
            'desea_liberar_precio' => (bool)$s->desea_liberar_precio,
            'estado' => (string)$s->estado,
            'created_at' => $s->created_at,
            'updated_at' => $s->updated_at,
        ];
    }

    public function index(Tarifa $tarifa, Request $request)
    {
        $this->authorize('viewAny', [TarifaServicio::class, $tarifa]);

        $params = GridParams::fromRequest(
            $request,
            ['codigo', 'descripcion', 'estado', 'precio_sin_igv', 'precio_con_igv', 'unidad'],
            'codigo'
        );

        $p = $this->service->paginate($tarifa, $params);

        $igv = ParametroSistema::getIgvPorcentaje();

        return response()->json([
            'data' => array_map(fn ($x) => $this->present($x, $igv), $p->items()),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
                'igv_porcentaje' => $igv,
            ],
        ]);
    }

    public function nextCodigo(Tarifa $tarifa, Request $request)
    {
        $this->authorize('create', [TarifaServicio::class, $tarifa]);

        $categoriaId = (int)$request->query('categoria_id', 0);
        $subcategoriaId = (int)$request->query('subcategoria_id', 0);

        return response()->json([
            'data' => $this->service->peekNextCodigo($tarifa, $categoriaId, $subcategoriaId),
        ]);
    }

    public function store(Tarifa $tarifa, TarifaServicioStoreRequest $request)
    {
        $this->authorize('create', [TarifaServicio::class, $tarifa]);

        $created = $this->service->create($tarifa, $request->validated());

        $igv = ParametroSistema::getIgvPorcentaje();
        $payload = ['data' => $this->present($created, $igv)];
        if ($this->service->lastPropagationResult !== null) {
            $payload['propagacion'] = $this->service->lastPropagationResult->toArray();
        }

        return response()->json($payload, 201);
    }

    public function update(Tarifa $tarifa, TarifaServicioUpdateRequest $request, TarifaServicio $servicio)
    {
        $this->authorize('update', $servicio);

        $updated = $this->service->update($tarifa, $servicio, $request->validated());

        $igv = ParametroSistema::getIgvPorcentaje();

        return response()->json(['data' => $this->present($updated, $igv)]);
    }

    public function deactivate(Tarifa $tarifa, TarifaServicio $servicio)
    {
        $this->authorize('deactivate', $servicio);

        $updated = $this->service->deactivate($tarifa, $servicio);

        $igv = ParametroSistema::getIgvPorcentaje();

        return response()->json(['data' => $this->present($updated, $igv)]);
    }
}

