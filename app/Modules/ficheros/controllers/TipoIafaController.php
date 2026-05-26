<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Core\grid\GridResponse;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\TipoIafa;
use App\Modules\ficheros\requests\TipoIafaStoreRequest;
use App\Modules\ficheros\requests\TipoIafaUpdateRequest;
use App\Modules\ficheros\services\TipoIafaService;
use Illuminate\Http\Request;

class TipoIafaController extends Controller
{
    public function __construct(private TipoIafaService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', TipoIafa::class);

        $params = GridParams::fromRequest($request, ['codigo', 'descripcion', 'estado'], 'codigo');

        return GridResponse::fromPaginator($this->service->paginate($params));
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

