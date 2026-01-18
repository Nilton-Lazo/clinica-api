<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\requests\ficheros\TarifaStoreRequest;
use App\Modules\admision\requests\ficheros\TarifaUpdateRequest;
use App\Modules\admision\services\ficheros\TarifaService;
use Illuminate\Http\Request;

class TarifaController extends Controller
{
    public function __construct(private TarifaService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Tarifa::class);

        $p = $this->service->paginate($request->only(['q', 'status', 'per_page', 'page']));

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

    public function nextCodigo()
    {
        $this->authorize('create', Tarifa::class);

        $codigo = $this->service->previewNextCodigo();

        return response()->json([
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function store(TarifaStoreRequest $request)
    {
        $this->authorize('create', Tarifa::class);

        $tarifa = $this->service->create($request->validated());

        return response()->json(['data' => $tarifa], 201);
    }

    public function update(TarifaUpdateRequest $request, Tarifa $tarifa)
    {
        $this->authorize('update', $tarifa);

        $updated = $this->service->update($tarifa, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function setBase(Tarifa $tarifa)
    {
        $this->authorize('update', $tarifa);

        $updated = $this->service->setBase($tarifa);

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Tarifa $tarifa)
    {
        $this->authorize('deactivate', $tarifa);

        $updated = $this->service->deactivate($tarifa);

        return response()->json(['data' => $updated]);
    }
}
