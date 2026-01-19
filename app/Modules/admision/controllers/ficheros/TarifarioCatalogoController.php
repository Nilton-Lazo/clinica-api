<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\requests\ficheros\TarifaServiciosIndexRequest;
use App\Modules\admision\services\ficheros\TarifarioCatalogoService;
use Illuminate\Http\Request;

class TarifarioCatalogoController extends Controller
{
    public function __construct(private TarifarioCatalogoService $service) {}

    public function tarifasOperativas(Request $request)
    {
        $this->authorize('viewAny', Tarifa::class);

        $items = $this->service->listTarifasOperativas($request->only(['q']));

        return response()->json(['data' => $items]);
    }

    public function tarifaBase()
    {
        $this->authorize('viewAny', Tarifa::class);

        $base = $this->service->getTarifaBase();

        return response()->json([
            'data' => $base->only(['id', 'codigo', 'descripcion_tarifa', 'tarifa_base', 'estado']),
        ]);
    }

    public function servicios(TarifaServiciosIndexRequest $request, Tarifa $tarifa)
    {
        $this->authorize('viewAny', Tarifa::class);

        $p = $this->service->paginateServicios($tarifa, $request->validated());

        return response()->json([
            'data' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ]);
    }

    public function arbolBase()
    {
        $this->authorize('viewAny', Tarifa::class);

        $payload = $this->service->arbolTarifaBase();

        return response()->json(['data' => $payload]);
    }
}
