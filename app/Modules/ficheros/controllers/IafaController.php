<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Core\grid\GridResponse;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\Iafa;
use App\Modules\ficheros\requests\IafaStoreRequest;
use App\Modules\ficheros\requests\IafaUpdateRequest;
use App\Modules\ficheros\services\IafaService;
use Illuminate\Http\Request;

class IafaController extends Controller
{
    public function __construct(private IafaService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Iafa::class);

        $params = GridParams::fromRequest($request, ['codigo', 'razon_social', 'estado'], 'codigo');

        return GridResponse::fromPaginator($this->service->paginate($params));
    }

    public function nextCodigo()
    {
        $this->authorize('create', Iafa::class);

        $codigo = $this->service->previewNextCodigo();

        return response()->json([
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function store(IafaStoreRequest $request)
    {
        $this->authorize('create', Iafa::class);

        $iafa = $this->service->create($request->validated());

        return response()->json(['data' => $iafa], 201);
    }

    public function update(IafaUpdateRequest $request, Iafa $iafa)
    {
        $this->authorize('update', $iafa);

        $updated = $this->service->update($iafa, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Iafa $iafa)
    {
        $this->authorize('deactivate', $iafa);

        $updated = $this->service->deactivate($iafa);

        return response()->json(['data' => $updated]);
    }
}

