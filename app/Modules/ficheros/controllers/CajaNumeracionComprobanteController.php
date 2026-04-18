<?php

namespace App\Modules\ficheros\controllers;

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
        $p = $this->service->paginate($request->only(['q', 'status', 'per_page', 'page']));
        return response()->json($this->service->serializePage($p));
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
