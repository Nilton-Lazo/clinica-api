<?php

namespace App\Modules\caja\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\CajaFormaPago;
use App\Modules\caja\requests\ComprobanteEmisionFraccionarPagosRequest;
use App\Modules\caja\services\ComprobanteEmisionFraccionarPagosService;
use Illuminate\Http\JsonResponse;

class ComprobanteEmisionFraccionarPagosController extends Controller
{
    public function __construct(
        private ComprobanteEmisionFraccionarPagosService $service,
    ) {}

    public function store(ComprobanteEmisionFraccionarPagosRequest $request, int $emisionComprobanteId): JsonResponse
    {
        $this->authorize('viewAny', CajaFormaPago::class);

        $this->service->ejecutar($request->user(), $emisionComprobanteId, $request->validated()['pagos']);

        return response()->json(['data' => ['ok' => true]]);
    }
}
