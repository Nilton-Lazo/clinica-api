<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\TipoIafa;
use App\Modules\admision\requests\ficheros\TipoIafaStoreRequest;
use App\Modules\admision\requests\ficheros\TipoIafaUpdateRequest;
use App\Modules\admision\services\ficheros\TipoIafaService;
use Illuminate\Http\Request;

class TipoIafaController extends Controller
{
    public function __construct(private TipoIafaService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', TipoIafa::class);

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
        $this->authorize('create', TipoIafa::class);

        $codigo = $this->service->previewNextCodigo();

        return response()->json([
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function store(TipoIafaStoreRequest $request)
    {
        $this->authorize('create', TipoIafa::class);

        $tipo = $this->service->create($request->validated());

        return response()->json(['data' => $tipo], 201);
    }

    public function update(TipoIafaUpdateRequest $request, TipoIafa $tipoIafa)
    {
        $this->authorize('update', $tipoIafa);

        $updated = $this->service->update($tipoIafa, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(TipoIafa $tipoIafa)
    {
        $this->authorize('deactivate', $tipoIafa);

        $updated = $this->service->deactivate($tipoIafa);

        return response()->json(['data' => $updated]);
    }
}
