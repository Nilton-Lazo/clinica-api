<?php

namespace App\Modules\ficheros\controllers;

use App\Core\grid\GridParams;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\CajaNumeracionComprobante;
use App\Modules\ficheros\requests\CajaNumeracionComprobanteStoreRequest;
use App\Modules\ficheros\requests\CajaNumeracionComprobanteUpdateRequest;
use App\Modules\ficheros\services\CajaNumeracionComprobanteService;
use Illuminate\Http\Request;

class CajaNumeracionComprobanteController extends Controller
{
    public function __construct(private CajaNumeracionComprobanteService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CajaNumeracionComprobante::class);
        $params = GridParams::fromRequest($request, ['serie', 'numero', 'estado'], 'serie');

        return response()->json($this->service->serializePage($this->service->paginate($params)));
    }

    public function store(CajaNumeracionComprobanteStoreRequest $request)
    {
        $this->authorize('create', CajaNumeracionComprobante::class);
        $row = $this->service->create($request->validated());
        return response()->json(['data' => $row], 201);
    }

    public function update(CajaNumeracionComprobanteUpdateRequest $request, CajaNumeracionComprobante $cajaNumeracionComprobante)
    {
        $this->authorize('update', $cajaNumeracionComprobante);
        $updated = $this->service->update($cajaNumeracionComprobante, $request->validated());
        return response()->json(['data' => $updated]);
    }

    public function deactivate(CajaNumeracionComprobante $cajaNumeracionComprobante)
    {
        $this->authorize('deactivate', $cajaNumeracionComprobante);
        $updated = $this->service->deactivate($cajaNumeracionComprobante);
        return response()->json(['data' => $updated]);
    }
}
