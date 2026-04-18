<?php

namespace App\Modules\admision\controllers\citas;

use App\Core\support\CuentaOrigen;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\models\CitaAtencion;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\PreFacturacionHospitalariaRegistro;
use App\Modules\admision\services\citas\CitaAtencionService;
use App\Modules\admision\services\citas\CuentaCitaService;
use App\Modules\emergencia\services\AtencionEmergenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CuentaCitaController extends Controller
{
    public function __construct(
        private CuentaCitaService $service,
        private AtencionEmergenciaService $atencionEmergenciaService,
        private CitaAtencionService $citaAtencionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AgendaCita::class);

        $p = $this->service->paginate($request->only(['q', 'per_page', 'page', 'emision_origen']));

        $items = [];
        foreach ($p->items() as $row) {
            $fechaRaw = $row->fecha ?? null;
            $fechaStr = null;
            if ($fechaRaw instanceof \DateTimeInterface) {
                $fechaStr = $fechaRaw->format('Y-m-d');
            } elseif (is_string($fechaRaw) && $fechaRaw !== '') {
                $fechaStr = substr($fechaRaw, 0, 10);
            }

            $items[] = [
                'nro_cuenta' => (string) $row->nro_cuenta,
                'origen' => (string) $row->origen,
                'origen_sigla' => $this->origenSigla((string) $row->origen),
                'origen_id' => (int) $row->origen_id,
                'nr' => $row->nr !== null && $row->nr !== '' ? (string) $row->nr : null,
                'hc' => $row->hc !== null && $row->hc !== '' ? (string) $row->hc : null,
                'apellidos_nombres' => trim((string) ($row->paciente_nombre ?? '')),
                'fecha' => $fechaStr,
                'estado' => $this->estadoListado($row->estado !== null ? (string) $row->estado : ''),
                'paciente_id' => $row->paciente_id !== null ? (int) $row->paciente_id : null,
                'paciente_plan_id' => $row->paciente_plan_id !== null ? (int) $row->paciente_plan_id : null,
            ];
        }

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ]);
    }

    public function show(string $nroCuenta): JsonResponse
    {
        $this->authorize('viewAny', AgendaCita::class);

        $cuenta = Cuenta::query()->where('nro_cuenta', $nroCuenta)->firstOrFail();

        if ($cuenta->origen === CuentaOrigen::REGISTRO_EMERGENCIA->value) {
            $detalle = $this->atencionEmergenciaService->datosParaAtencion((int) $cuenta->origen_id);

            return response()->json([
                'data' => [
                    'cuenta' => $this->serializeCuenta($cuenta),
                    'detalle' => $detalle,
                ],
            ]);
        }

        if ($cuenta->origen === CuentaOrigen::CITA_ATENCION->value) {
            $atencion = CitaAtencion::query()->findOrFail((int) $cuenta->origen_id);
            $detalle = $this->citaAtencionService->datosParaAtencion((int) $atencion->agenda_cita_id);

            return response()->json([
                'data' => [
                    'cuenta' => $this->serializeCuenta($cuenta),
                    'detalle' => $detalle,
                ],
            ]);
        }

        if ($cuenta->origen === CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value) {
            $registro = PreFacturacionHospitalariaRegistro::query()->findOrFail((int) $cuenta->origen_id);

            return response()->json([
                'data' => [
                    'cuenta' => $this->serializeCuenta($cuenta),
                    'detalle' => [
                        'pre_facturacion_hospitalaria' => true,
                        'form' => $registro->payload ?? [],
                    ],
                ],
            ]);
        }

        abort(404);
    }

    private function serializeCuenta(Cuenta $cuenta): array
    {
        return [
            'id' => (int) $cuenta->id,
            'nro_cuenta' => (string) $cuenta->nro_cuenta,
            'origen' => (string) $cuenta->origen,
            'origen_id' => (int) $cuenta->origen_id,
            'paciente_id' => $cuenta->paciente_id !== null ? (int) $cuenta->paciente_id : null,
            'paciente_plan_id' => $cuenta->paciente_plan_id !== null ? (int) $cuenta->paciente_plan_id : null,
            'tarifa_id' => $cuenta->tarifa_id !== null ? (int) $cuenta->tarifa_id : null,
            'estado' => $cuenta->estado !== null ? (string) $cuenta->estado : '',
        ];
    }

    private function origenSigla(string $origen): string
    {
        return match ($origen) {
            CuentaOrigen::CITA_ATENCION->value => 'AMB',
            CuentaOrigen::REGISTRO_EMERGENCIA->value => 'EM',
            CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value => 'HOS',
            default => $origen,
        };
    }

    private function estadoListado(string $estado): string
    {
        $v = strtoupper(trim($estado));
        if ($v === '') {
            return '';
        }

        return match ($v) {
            'ACTIVO' => 'REGISTRADO',
            'CANCELADO_LISTO_PARA_FACTURAR' => 'CANCELADO_LISTO_PARA_FACTURAR',
            default => $v,
        };
    }
}
