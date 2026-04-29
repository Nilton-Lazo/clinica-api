<?php

namespace App\Modules\caja\services;

use App\Models\User;
use App\Modules\admision\models\CajaFormaPago;
use App\Modules\admision\models\CajaMedioPago;
use App\Modules\caja\models\CajaApertura;
use App\Modules\caja\models\CajaEmisionComprobante;
use App\Modules\ficheros\services\CajaNumeracionComprobanteService;
use Illuminate\Validation\ValidationException;

class ReporteIngresosCajaService
{
    public function __construct(
        private CajaNumeracionComprobanteService $numeracion,
    ) {}

    public function bootstrap(User $actor): array
    {
        $rawSeries = $this->numeracion->listAllActivosForEmision();
        $series = [];
        foreach ($rawSeries as $row) {
            $serie = (string) ($row['serie'] ?? '');
            $td = (string) ($row['tipo_documento_descripcion'] ?? '');
            $series[] = [
                'id' => (int) ($row['id'] ?? 0),
                'serie' => $serie,
                'label' => trim($serie.($td !== '' ? ' · '.$td : '')),
            ];
        }

        $formaContado = CajaFormaPago::query()
            ->activos()
            ->where(function ($q) {
                $q->whereRaw('UPPER(TRIM(codigo)) = ?', ['CONTADO'])
                    ->orWhereRaw('LOWER(descripcion) LIKE ?', ['%contado%']);
            })
            ->orderBy('id')
            ->first();

        $mediosContado = [];
        if ($formaContado) {
            $fid = (int) $formaContado->id;
            $medios = CajaMedioPago::query()
                ->activos()
                ->with('formasPago')
                ->orderBy('codigo')
                ->get();
            foreach ($medios as $m) {
                $ids = $m->formasPago->pluck('id')->map(fn ($x) => (int) $x)->all();
                if (! in_array($fid, $ids, true)) {
                    continue;
                }
                $mediosContado[] = [
                    'id' => (int) $m->id,
                    'codigo' => (string) $m->codigo,
                    'descripcion' => (string) $m->descripcion,
                ];
            }
        }

        $tz = (string) config('app.timezone');
        $aperturas = [];
        $rows = CajaApertura::query()
            ->where('user_recepciona_id', $actor->id)
            ->with(['userRecepciona:id,username'])
            ->orderByDesc('apertura_at')
            ->limit(80)
            ->get();

        foreach ($rows as $a) {
            $u = $a->userRecepciona;
            $loginUsuario = '—';
            if ($u) {
                $loginUsuario = trim((string) ($u->username ?? ''));
                if ($loginUsuario === '') {
                    $loginUsuario = (string) $u->id;
                }
            }
            $aperturas[] = [
                'id' => (string) $a->id,
                'codigo' => (string) $a->codigo,
                'usuario' => $loginUsuario,
                'fecha' => $a->apertura_at?->copy()->setTimezone($tz)->format('d-m-Y') ?? '',
                'monto_apertura' => number_format((float) $a->monto_inicio, 2, '.', ''),
                'monto_cierre' => $a->cerrada_at !== null && $a->monto_cierre !== null
                    ? number_format((float) $a->monto_cierre, 2, '.', '')
                    : '—',
                'estado' => $a->cerrada_at === null ? 'APERTURADA' : 'CERRADA',
                'tipo' => (string) $a->tipo,
            ];
        }

        $abierta = CajaApertura::query()
            ->where('user_recepciona_id', $actor->id)
            ->whereNull('cerrada_at')
            ->orderByDesc('apertura_at')
            ->first();

        return [
            'series' => $series,
            'medios_contado' => $mediosContado,
            'aperturas' => $aperturas,
            'apertura_preferida_id' => $abierta !== null ? (string) $abierta->id : null,
        ];
    }

    public function movimientos(User $actor, int $cajaAperturaId, ?string $numeracionId): array
    {
        $a = CajaApertura::query()->whereKey($cajaAperturaId)->first();
        if (! $a) {
            throw ValidationException::withMessages([
                'caja_apertura_id' => ['La apertura seleccionada no existe. Actualiza el reporte y selecciona otra apertura.'],
            ]);
        }
        if ((int) $a->user_recepciona_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'caja_apertura_id' => ['No tienes acceso a esta apertura de caja. Selecciona una apertura propia.'],
            ]);
        }

        $emisiones = CajaEmisionComprobante::query()
            ->where('caja_apertura_id', $a->id)
            ->orderBy('created_at')
            ->get();

        $totalesPorMedio = [];
        $sumFacturas = 0.0;
        $sumBoletas = 0.0;
        $sumRecibo = 0.0;
        $movimientos = [];

        foreach ($emisiones as $e) {
            $snap = is_array($e->snapshot) ? $e->snapshot : [];
            $form = isset($snap['form']) && is_array($snap['form']) ? $snap['form'] : [];
            $labels = isset($snap['labels']) && is_array($snap['labels']) ? $snap['labels'] : [];

            $emisionNumeracionId = $e->numeracion_comprobante_id !== null
                ? (string) $e->numeracion_comprobante_id
                : (string) ($form['numeracionId'] ?? '');
            if ($numeracionId !== null && $numeracionId !== '' && $emisionNumeracionId !== (string) $numeracionId) {
                continue;
            }

            $total = $e->total_paciente !== null
                ? (float) $e->total_paciente
                : $this->totalDesdeSnapshot($snap);
            $medioId = (int) ($form['medioPagoId'] ?? 0);
            if ($medioId > 0) {
                $key = (string) $medioId;
                $totalesPorMedio[$key] = ($totalesPorMedio[$key] ?? 0.0) + $total;
            }

            $tipoComp = strtolower((string) ($labels['tipo_comprobante'] ?? ''));
            if (str_contains($tipoComp, 'factura')) {
                $sumFacturas += $total;
            } elseif (str_contains($tipoComp, 'boleta')) {
                $sumBoletas += $total;
            } elseif (str_contains($tipoComp, 'recibo')) {
                $sumRecibo += $total;
            }

            $medioLabel = (string) ($labels['medio_pago'] ?? '');
            $lineas = isset($snap['servicios_lineas']) && is_array($snap['servicios_lineas']) ? $snap['servicios_lineas'] : [];
            $primerServicio = '—';
            if ($lineas !== [] && is_array($lineas[0])) {
                $tsId = (int) ($lineas[0]['tarifa_servicio_id'] ?? 0);
                $primerServicio = $tsId > 0 ? 'Servicio #'.$tsId : '—';
            }

            $numeroComprobante = (string) ($form['correlativo'] ?? '');
            if ($e->numero_emitido !== null) {
                $numeroComprobante = str_pad((string) $e->numero_emitido, 7, '0', STR_PAD_LEFT);
            }

            $movimientos[] = [
                'id' => (string) $e->id,
                'cuenta' => (string) $e->nro_cuenta,
                'paciente' => (string) ($form['paciente'] ?? ''),
                'medico_servicio' => $primerServicio,
                'tipo_comprobante' => (string) ($labels['tipo_comprobante'] ?? ''),
                'num_comprobante' => $numeroComprobante,
                'total' => number_format($total, 2, '.', ''),
                'cuenta_pago' => (string) ($e->numero_operacion ?? ''),
                'estado' => (string) ($labels['estado_emision'] ?? (string) ($form['estadoEmision'] ?? '')),
                'pago_fracc' => '—',
                'medio_pago' => $medioLabel,
                'tipo' => (string) ($e->cuenta_origen ?? ''),
                'adelanto' => '—',
                'usuario_elimina' => '—',
            ];
        }

        $totalesPorMedioFmt = [];
        foreach ($totalesPorMedio as $k => $v) {
            $totalesPorMedioFmt[$k] = number_format((float) $v, 2, '.', '');
        }

        $totalGeneral = array_sum(array_map(fn ($v) => (float) $v, $totalesPorMedio));

        return [
            'movimientos' => $movimientos,
            'totales_por_medio' => $totalesPorMedioFmt,
            'totales_documento' => [
                'facturas' => number_format($sumFacturas, 2, '.', ''),
                'boletas' => number_format($sumBoletas, 2, '.', ''),
                'recibo_caja' => number_format($sumRecibo, 2, '.', ''),
            ],
            'total_general' => number_format($totalGeneral, 2, '.', ''),
        ];
    }

    private function totalDesdeSnapshot(array $snap): float
    {
        $lineas = $snap['servicios_lineas'] ?? [];
        if (! is_array($lineas)) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($lineas as $ln) {
            if (! is_array($ln)) {
                continue;
            }
            $qty = isset($ln['cantidad']) ? (float) $ln['cantidad'] : 1.0;
            $p = isset($ln['precio_con_igv']) ? (float) $ln['precio_con_igv'] : 0.0;
            $sum += $qty * $p;
        }

        return round($sum, 2);
    }
}
