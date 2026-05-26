<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\CajaMedioPago;
use App\Modules\ficheros\requests\CajaMedioPagoStoreRequest;
use App\Modules\ficheros\requests\CajaMedioPagoUpdateRequest;
use App\Modules\ficheros\services\CajaMedioPagoService;
use Illuminate\Http\Request;

class CajaMedioPagoController extends Controller
{
    public function __construct(private CajaMedioPagoService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CajaMedioPago::class);
        $params = GridParams::fromRequest($request, ['codigo', 'descripcion', 'estado'], 'codigo');

        return response()->json($this->service->serializePage($this->service->paginate($params)));
    }

    public function nextCodigo()
    {
        $this->authorize('create', CajaMedioPago::class);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo(),
            ],
        ]);
    }

    public function store(CajaMedioPagoStoreRequest $request)
    {
        $this->authorize('create', CajaMedioPago::class);
        $row = $this->service->create($request->validated());
        return response()->json(['data' => $row], 201);
    }

    public function update(CajaMedioPagoUpdateRequest $request, CajaMedioPago $cajaMedioPago)
    {
        $this->authorize('update', $cajaMedioPago);
        $updated = $this->service->update($cajaMedioPago, $request->validated());
        return response()->json(['data' => $updated]);
    }

    public function deactivate(CajaMedioPago $cajaMedioPago)
    {
        $this->authorize('deactivate', $cajaMedioPago);
        $updated = $this->service->deactivate($cajaMedioPago);
        return response()->json(['data' => $updated]);
    }
}
