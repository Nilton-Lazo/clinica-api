<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Core\grid\GridResponse;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\Cliente;
use App\Modules\ficheros\requests\ClienteStoreRequest;
use App\Modules\ficheros\requests\ClienteUpdateRequest;
use App\Modules\ficheros\services\ClienteService;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function __construct(private ClienteService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Cliente::class);

        $params = GridParams::fromRequest($request, ['codigo', 'nombre', 'estado'], 'codigo');

        return GridResponse::fromPaginator($this->service->paginate($params));
    }

    public function nextCodigo()
    {
        $this->authorize('create', Cliente::class);

        $codigo = $this->service->previewNextCodigo();

        return response()->json([
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function store(ClienteStoreRequest $request)
    {
        $this->authorize('create', Cliente::class);

        $cliente = $this->service->create($request->validated());

        return response()->json(['data' => $cliente], 201);
    }

    public function update(ClienteUpdateRequest $request, Cliente $cliente)
    {
        $this->authorize('update', $cliente);

        $updated = $this->service->update($cliente, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Cliente $cliente)
    {
        $this->authorize('deactivate', $cliente);

        $updated = $this->service->deactivate($cliente);

        return response()->json(['data' => $updated]);
    }
}
