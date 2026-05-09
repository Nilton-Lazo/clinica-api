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
        $row->loadMissing('numeracionComprobante');

        return response()->json([
            'data' => [
                'id' => $row->id,
                'nro_cuenta' => $row->nro_cuenta,
                'numeracion_comprobante_id' => (int) $row->numeracion_comprobante_id,
                'tipo_documento_id' => $row->numeracionComprobante !== null
                    ? (int) $row->numeracionComprobante->tipo_documento_id
                    : null,
                'serie' => $row->serie,
                'numero_emitido' => $row->numero_emitido,
                'numero_formateado' => $row->numero_emitido !== null
                    ? str_pad((string) $row->numero_emitido, 7, '0', STR_PAD_LEFT)
                    : null,
                'fecha_vencimiento' => $row->fecha_vencimiento?->format('Y-m-d'),
                'total_paciente' => $row->total_paciente !== null ? (string) $row->total_paciente : null,
                'total_lineas' => $row->total_lineas !== null ? (int) $row->total_lineas : 0,
                'created_at' => $row->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
