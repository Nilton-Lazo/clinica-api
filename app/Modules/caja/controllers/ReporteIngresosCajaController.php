<?php

namespace App\Modules\caja\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\CajaFormaPago;
use App\Modules\caja\services\ReporteIngresosCajaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReporteIngresosCajaController extends Controller
{
    public function __construct(
        private ReporteIngresosCajaService $service,
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CajaFormaPago::class);

        return response()->json($this->service->bootstrap($request->user()));
    }

    public function movimientos(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CajaFormaPago::class);

        $v = $request->validate([
            'caja_apertura_id' => ['required', 'integer', 'exists:caja_aperturas,id'],
            'numeracion_id' => ['nullable', 'string', 'max:32'],
        ]);

        $data = $this->service->movimientos(
            $request->user(),
            (int) $v['caja_apertura_id'],
            isset($v['numeracion_id']) ? trim((string) $v['numeracion_id']) : null
        );

        return response()->json(['data' => $data]);
    }
}
