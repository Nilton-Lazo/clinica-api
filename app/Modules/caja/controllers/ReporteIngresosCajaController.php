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

        $v = $request->validate([
            'aperturas_page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', 'nullable', 'string', 'in:codigo,usuario,fecha,monto_apertura,monto_cierre,estado,tipo'],
            'sort_dir' => ['sometimes', 'string', 'in:asc,desc'],
        ]);

        $page = isset($v['aperturas_page']) ? (int) $v['aperturas_page'] : null;
        $sort = isset($v['sort']) ? trim((string) $v['sort']) : null;
        if ($sort === '') {
            $sort = null;
        }
        $sortDir = isset($v['sort_dir']) ? (string) $v['sort_dir'] : 'desc';

        return response()->json($this->service->bootstrap($request->user(), $page, $sort, $sortDir));
    }

    public function movimientos(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CajaFormaPago::class);

        $v = $request->validate([
            'caja_apertura_id' => ['required', 'integer', 'exists:caja_aperturas,id'],
            'numeracion_id' => ['nullable', 'string', 'max:32'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'nullable', 'string', 'in:nro_cuenta,paciente,medico,tipo_comprobante,num_comprobante,total,estado,pago_fracc,medio_pago,origen_sigla,adelanto'],
            'sort_dir' => ['sometimes', 'string', 'in:asc,desc'],
        ], [
            'caja_apertura_id.required' => 'Selecciona una apertura de caja para consultar movimientos.',
            'caja_apertura_id.integer' => 'Selecciona una apertura de caja válida.',
            'caja_apertura_id.exists' => 'La apertura de caja seleccionada no existe.',
            'numeracion_id.string' => 'La serie del comprobante debe ser texto.',
            'numeracion_id.max' => 'La serie del comprobante no debe superar 32 caracteres.',
        ]);

        $sort = isset($v['sort']) ? trim((string) $v['sort']) : null;
        if ($sort === '') {
            $sort = null;
        }
        $sortDir = isset($v['sort_dir']) ? (string) $v['sort_dir'] : 'asc';

        $data = $this->service->movimientos(
            $request->user(),
            (int) $v['caja_apertura_id'],
            isset($v['numeracion_id']) ? trim((string) $v['numeracion_id']) : null,
            isset($v['page']) ? (int) $v['page'] : 1,
            isset($v['per_page']) ? (int) $v['per_page'] : 25,
            $sort,
            $sortDir,
        );

        return response()->json(['data' => $data]);
    }
}
