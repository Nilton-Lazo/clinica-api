<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaRecargoNoche;
use App\Modules\admision\requests\ficheros\TarifaRecargoNocheStoreRequest;
use App\Modules\admision\requests\ficheros\TarifaRecargoNocheUpdateRequest;
use App\Modules\admision\services\ficheros\TarifaRecargoNocheService;
use Illuminate\Http\Request;

class TarifaRecargoNocheController extends Controller
{
    public function __construct(private TarifaRecargoNocheService $service) {}

    private function present(TarifaRecargoNoche $r): array
    {
        $cat = $r->relationLoaded('tarifaCategoria') ? $r->tarifaCategoria : null;
        return [
            'id' => (int)$r->id,
            'tarifa_id' => (int)$r->tarifa_id,
            'tarifa_categoria_id' => (int)$r->tarifa_categoria_id,
            'categoria_codigo' => $cat ? (string)$cat->codigo : null,
            'categoria_nombre' => $cat ? (string)$cat->nombre : null,
            'porcentaje' => (float)$r->porcentaje,
            'hora_desde' => is_string($r->hora_desde)
                ? substr($r->hora_desde, 0, 5)
                : \Carbon\Carbon::parse($r->hora_desde)->format('H:i'),
            'hora_hasta' => $r->hora_hasta !== null
                ? (is_string($r->hora_hasta) ? substr($r->hora_hasta, 0, 5) : \Carbon\Carbon::parse($r->hora_hasta)->format('H:i'))
                : null,
            'estado' => (string)($r->estado ?? 'ACTIVO'),
            'created_at' => $r->created_at,
            'updated_at' => $r->updated_at,
        ];
    }

    public function index(Tarifa $tarifa, Request $request)
    {
        $this->authorize('viewAny', Tarifa::class);

        $status = $request->query('status');

        $items = $this->service->listByTarifa($tarifa, $status);

        return response()->json([
            'data' => $items->map(fn ($r) => $this->present($r))->values()->all(),
        ]);
    }

    public function store(Tarifa $tarifa, TarifaRecargoNocheStoreRequest $request)
    {
        $this->authorize('viewAny', Tarifa::class);

        $recargo = $this->service->create($tarifa, $request->validated());
        $recargo->load('tarifaCategoria');

        return response()->json(['data' => $this->present($recargo)], 201);
    }

    public function update(Tarifa $tarifa, TarifaRecargoNocheUpdateRequest $request, TarifaRecargoNoche $recargoNoche)
    {
        $this->authorize('viewAny', Tarifa::class);

        if ((int)$recargoNoche->tarifa_id !== (int)$tarifa->id) {
            abort(404);
        }

        $updated = $this->service->update($recargoNoche, $request->validated());

        return response()->json(['data' => $this->present($updated)]);
    }

    public function deactivate(Tarifa $tarifa, TarifaRecargoNoche $recargoNoche)
    {
        $this->authorize('viewAny', Tarifa::class);

        if ((int)$recargoNoche->tarifa_id !== (int)$tarifa->id) {
            abort(404);
        }

        $updated = $this->service->deactivate($recargoNoche);

        return response()->json(['data' => $this->present($updated)]);
    }
}
