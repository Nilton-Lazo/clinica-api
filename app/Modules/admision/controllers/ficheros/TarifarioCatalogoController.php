<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\requests\ficheros\TarifaServiciosIndexRequest;
use App\Modules\admision\services\ficheros\TarifarioCatalogoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function servicios(TarifaServiciosIndexRequest $request)
    {
        $this->authorize('viewAny', Tarifa::class);

        $path = $request->path();
        $tarifaId = $request->route('tarifa');

        Log::channel('single')->info('TarifarioCatalogoController::servicios', [
            'path' => $path,
            'url' => $request->url(),
            'route_name' => $request->route()?->getName(),
            'route_params' => $request->route()?->parameters() ?? [],
            'tarifa_param_raw' => $tarifaId,
            'tarifa_param_type' => gettype($tarifaId),
        ]);

        // Fallback: extraer id de la path por si el route parameter llega mal (ej. literal "{tarifa}")
        if (!is_numeric($tarifaId) || (int) $tarifaId < 1) {
            if (preg_match('#/tarifas/(\d+)/servicios#', '/' . $path, $m)) {
                $tarifaId = (int) $m[1];
                Log::channel('single')->info('TarifarioCatalogoController::servicios fallback from path', ['tarifa_id' => $tarifaId]);
            }
        }

        if (!is_numeric($tarifaId) || (int) $tarifaId < 1) {
            abort(404, 'Tarifa no encontrada.');
        }
        $tarifa = Tarifa::query()->findOrFail((int) $tarifaId);

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
