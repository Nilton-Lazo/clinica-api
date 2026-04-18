<?php

namespace App\Modules\caja\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\CajaFormaPago;
use App\Modules\caja\requests\ComprobanteEmisionRegistrarRequest;
use App\Modules\caja\services\ComprobanteEmisionRegistrarService;
use Illuminate\Http\JsonResponse;

class ComprobanteEmisionRegistrarController extends Controller
{
    public function __construct(
        private ComprobanteEmisionRegistrarService $service,
    ) {}

    public function store(ComprobanteEmisionRegistrarRequest $request): JsonResponse
    {
        $this->authorize('viewAny', CajaFormaPago::class);

        $validated = $request->validated();
        $row = $this->service->registrar($request->user(), $validated);

        return response()->json([
            'data' => [
                'id' => $row->id,
                'nro_cuenta' => $row->nro_cuenta,
                'created_at' => $row->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
