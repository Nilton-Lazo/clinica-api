<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaServicio;
use App\Modules\admision\requests\ficheros\TarifaServicioStoreRequest;
use App\Modules\admision\requests\ficheros\TarifaServicioUpdateRequest;
use App\Modules\admision\services\ficheros\TarifaServicioService;
use Illuminate\Http\Request;

class TarifaServicioController extends Controller
{
    public function __construct(private TarifaServicioService $service) {}

    private function present(TarifaServicio $s): array
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
            'unidad' => $s->unidad,
            'estado' => (string)$s->estado,
            'created_at' => $s->created_at,
            'updated_at' => $s->updated_at,
        ];
    }

    public function index(Tarifa $tarifa, Request $request)
    {
        $this->authorize('viewAny', [TarifaServicio::class, $tarifa]);

        $p = $this->service->paginate($tarifa, $request->only([
            'q', 'status', 'categoria_id', 'subcategoria_id', 'per_page', 'page'
        ]));

        return response()->json([
            'data' => array_map(fn ($x) => $this->present($x), $p->items()),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
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

        return response()->json(['data' => $this->present($created)], 201);
    }

    public function update(Tarifa $tarifa, TarifaServicioUpdateRequest $request, TarifaServicio $servicio)
    {
        $this->authorize('update', $servicio);

        $updated = $this->service->update($tarifa, $servicio, $request->validated());

        return response()->json(['data' => $this->present($updated)]);
    }

    public function deactivate(Tarifa $tarifa, TarifaServicio $servicio)
    {
        $this->authorize('deactivate', $servicio);

        $updated = $this->service->deactivate($tarifa, $servicio);

        return response()->json(['data' => $this->present($updated)]);
    }
}
