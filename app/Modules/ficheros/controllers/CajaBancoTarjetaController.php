<?php

namespace App\Modules\ficheros\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\CajaBancoTarjeta;
use App\Modules\ficheros\requests\CajaBancoTarjetaStoreRequest;
use App\Modules\ficheros\requests\CajaBancoTarjetaUpdateRequest;
use App\Modules\ficheros\services\CajaBancoTarjetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CajaBancoTarjetaController extends Controller
{
    public function __construct(private CajaBancoTarjetaService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CajaBancoTarjeta::class);
        $p = $this->service->paginate($request->only(['q', 'status', 'per_page', 'page']));

        return response()->json($this->service->serializePage($p));
    }

    public function mediosDisponibles(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CajaBancoTarjeta::class);
        $raw = (string) $request->query('forma_pago_ids', '');
        $ids = array_values(array_filter(array_map('intval', explode(',', $raw))));

        return response()->json([
            'data' => $this->service->mediosDisponiblesPorFormas($ids),
        ]);
    }

    public function nextCodigo(): JsonResponse
    {
        $this->authorize('create', CajaBancoTarjeta::class);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo(),
            ],
        ]);
    }

    public function store(CajaBancoTarjetaStoreRequest $request): JsonResponse
    {
        $this->authorize('create', CajaBancoTarjeta::class);
        $row = $this->service->create($request->validated());

        return response()->json(['data' => $row], 201);
    }

    public function update(CajaBancoTarjetaUpdateRequest $request, CajaBancoTarjeta $cajaBancoTarjeta): JsonResponse
    {
        $this->authorize('update', $cajaBancoTarjeta);
        $updated = $this->service->update($cajaBancoTarjeta, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(CajaBancoTarjeta $cajaBancoTarjeta): JsonResponse
    {
        $this->authorize('deactivate', $cajaBancoTarjeta);
        $updated = $this->service->deactivate($cajaBancoTarjeta);

        return response()->json(['data' => $updated]);
    }
}
