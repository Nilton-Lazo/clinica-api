<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Core\grid\GridResponse;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\Paquete;
use App\Modules\ficheros\requests\PaqueteStoreRequest;
use App\Modules\ficheros\requests\PaqueteUpdateRequest;
use App\Modules\ficheros\services\PaqueteService;
use Illuminate\Http\Request;

class PaqueteController extends Controller
{
    public function __construct(private PaqueteService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Paquete::class);

        $params = GridParams::fromRequest($request, ['codigo', 'descripcion', 'estado'], 'codigo');

        return GridResponse::fromPaginator($this->service->paginate($params));
    }

    public function nextCodigo()
    {
        $this->authorize('create', Paquete::class);

        $codigo = $this->service->previewNextCodigo();

        return response()->json([
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function store(PaqueteStoreRequest $request)
    {
        $this->authorize('create', Paquete::class);

        $paquete = $this->service->create($request->validated());

        return response()->json(['data' => $paquete], 201);
    }

    public function update(PaqueteUpdateRequest $request, Paquete $paquete)
    {
        $this->authorize('update', $paquete);

        $updated = $this->service->update($paquete, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Paquete $paquete)
    {
        $this->authorize('deactivate', $paquete);

        $updated = $this->service->deactivate($paquete);

        return response()->json(['data' => $updated]);
    }
}
