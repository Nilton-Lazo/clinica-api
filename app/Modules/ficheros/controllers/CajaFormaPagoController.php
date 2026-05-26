<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Core\grid\GridResponse;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\CajaFormaPago;
use App\Modules\ficheros\requests\CajaFormaPagoStoreRequest;
use App\Modules\ficheros\requests\CajaFormaPagoUpdateRequest;
use App\Modules\ficheros\services\CajaFormaPagoService;
use Illuminate\Http\Request;

class CajaFormaPagoController extends Controller
{
    public function __construct(private CajaFormaPagoService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CajaFormaPago::class);

        $params = GridParams::fromRequest($request, ['codigo', 'descripcion', 'estado'], 'codigo');

        return GridResponse::fromPaginator($this->service->paginate($params));
    }

    public function nextCodigo()
    {
        $this->authorize('create', CajaFormaPago::class);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo(),
            ],
        ]);
    }

    public function store(CajaFormaPagoStoreRequest $request)
    {
        $this->authorize('create', CajaFormaPago::class);

        $formaPago = $this->service->create($request->validated());

        return response()->json(['data' => $formaPago], 201);
    }

    public function update(CajaFormaPagoUpdateRequest $request, CajaFormaPago $cajaFormaPago)
    {
        $this->authorize('update', $cajaFormaPago);

        $updated = $this->service->update($cajaFormaPago, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(CajaFormaPago $cajaFormaPago)
    {
        $this->authorize('deactivate', $cajaFormaPago);

        $updated = $this->service->deactivate($cajaFormaPago);

        return response()->json(['data' => $updated]);
    }
}
