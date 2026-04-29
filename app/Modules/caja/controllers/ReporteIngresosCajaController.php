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
        ], [
            'caja_apertura_id.required' => 'Selecciona una apertura de caja para consultar movimientos.',
            'caja_apertura_id.integer' => 'Selecciona una apertura de caja válida.',
            'caja_apertura_id.exists' => 'La apertura de caja seleccionada no existe.',
            'numeracion_id.string' => 'La serie del comprobante debe ser texto.',
            'numeracion_id.max' => 'La serie del comprobante no debe superar 32 caracteres.',
        ]);

        $data = $this->service->movimientos(
            $request->user(),
            (int) $v['caja_apertura_id'],
            isset($v['numeracion_id']) ? trim((string) $v['numeracion_id']) : null
        );

        return response()->json(['data' => $data]);
    }
}
