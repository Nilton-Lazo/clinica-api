<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Core\grid\GridResponse;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\TipoCliente;
use App\Modules\ficheros\requests\TipoClienteStoreRequest;
use App\Modules\ficheros\requests\TipoClienteUpdateRequest;
use App\Modules\ficheros\services\TipoClienteService;
use Illuminate\Http\Request;

class TipoClienteController extends Controller
{
    public function __construct(private TipoClienteService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', TipoCliente::class);

        $params = GridParams::fromRequest($request, ['codigo', 'descripcion', 'estado'], 'codigo');

        return GridResponse::fromPaginator($this->service->paginate($params));
    }

    public function nextCodigo()
    {
        $this->authorize('create', TipoCliente::class);

        $codigo = $this->service->previewNextCodigo();

        return response()->json([
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function store(TipoClienteStoreRequest $request)
    {
        $this->authorize('create', TipoCliente::class);

        $tipoCliente = $this->service->create($request->validated());

        return response()->json(['data' => $tipoCliente], 201);
    }

    public function update(TipoClienteUpdateRequest $request, TipoCliente $tipoCliente)
    {
        $this->authorize('update', $tipoCliente);

        $updated = $this->service->update($tipoCliente, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(TipoCliente $tipoCliente)
    {
        $this->authorize('deactivate', $tipoCliente);

        $updated = $this->service->deactivate($tipoCliente);

        return response()->json(['data' => $updated]);
    }
}

