<?php

namespace App\Modules\caja\services;

use App\Core\support\CodigoCorrelativo;
use App\Core\support\CuentaOrigen;
use App\Models\User;
use App\Modules\admision\models\CajaFormaPago;
use App\Modules\admision\models\CajaMedioPago;
use App\Modules\admision\models\CitaAtencion;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\PreFacturacionHospitalariaRegistro;
use App\Modules\admision\models\RegistroEmergencia;
use App\Modules\caja\models\CajaApertura;
use App\Modules\caja\models\CajaEmisionComprobante;
use App\Modules\ficheros\services\CajaNumeracionComprobanteService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ReporteIngresosCajaService
{
    public function __construct(
        private CajaNumeracionComprobanteService $numeracion,
    ) {}

    public function bootstrap(User $actor, ?int $aperturasPage = null): array
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
            $medios = CodigoCorrelativo::orderByCodigoAsc(
                CajaMedioPago::query()->activos()->with('formasPago')
            )->get();
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

        $abierta = CajaApertura::query()
            ->where('user_recepciona_id', $actor->id)
            ->whereNull('cerrada_at')
            ->orderByDesc('apertura_at')
            ->orderByDesc('id')
            ->first();

        $perPageAperturas = 7;
        $idsOrdered = CajaApertura::query()
            ->where('user_recepciona_id', $actor->id)
            ->orderByDesc('apertura_at')
            ->orderByDesc('id')
            ->pluck('id');

        $totalAperturas = $idsOrdered->count();
        $lastPageAperturas = max(1, (int) ceil(max(1, $totalAperturas) / $perPageAperturas));

        $pageAperturas = 1;
        if ($aperturasPage !== null) {
            $pageAperturas = max(1, min((int) $aperturasPage, $lastPageAperturas));
        } elseif ($abierta !== null) {
            $idx = $idsOrdered->search(fn ($id) => (int) $id === (int) $abierta->id);
            if ($idx !== false) {
                $pageAperturas = (int) floor((int) $idx / $perPageAperturas) + 1;
            }
        }

        $sliceIds = $idsOrdered->slice(($pageAperturas - 1) * $perPageAperturas, $perPageAperturas)->values();
        $rowsById = $sliceIds->isEmpty()
            ? collect()
            : CajaApertura::query()
                ->whereIn('id', $sliceIds->all())
                ->with(['userRecepciona:id,username'])
                ->get()
                ->keyBy('id');

        $aperturas = [];
        foreach ($sliceIds as $aid) {
            $a = $rowsById->get($aid);
            if (! $a) {
                continue;
            }
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
                'ajuste_cierre' => $a->cerrada_at !== null && $a->ajuste_cierre !== null
                    ? number_format((float) $a->ajuste_cierre, 3, '.', '')
                    : null,
                'estado' => $a->cerrada_at === null ? 'APERTURADA' : 'CERRADA',
                'tipo' => (string) $a->tipo,
            ];
        }

        $mediosAdicionales = [
            ['id' => -1, 'codigo' => 'ADC', 'descripcion' => 'Adelantos / Custodia'],
            ['id' => -2, 'codigo' => 'NCR', 'descripcion' => 'Nota de crédito'],
            ['id' => -3, 'codigo' => 'EGR', 'descripcion' => 'Egresos'],
            ['id' => -4, 'codigo' => 'ING', 'descripcion' => 'Ingresos'],
        ];

        return [
            'series' => $series,
            'medios_contado' => $mediosContado,
            'medios_adicionales' => $mediosAdicionales,
            'aperturas' => $aperturas,
            'aperturas_meta' => [
                'current_page' => $pageAperturas,
                'per_page' => $perPageAperturas,
                'total' => $totalAperturas,
                'last_page' => $lastPageAperturas,
            ],
            'apertura_preferida_id' => $abierta !== null ? (string) $abierta->id : null,
        ];
    }

    public function movimientos(User $actor, int $cajaAperturaId, ?string $numeracionId, int $page = 1, int $perPage = 25): array
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
            ->with([
                'pagos.medioPago:id,codigo,descripcion',
                'numeracionComprobante.tipoDocumento:id,codigo,descripcion',
            ])
            ->orderBy('created_at')
            ->get();

        $filtradas = $emisiones->filter(function (CajaEmisionComprobante $e) use ($numeracionId) {
            $snap = is_array($e->snapshot) ? $e->snapshot : [];
            $form = isset($snap['form']) && is_array($snap['form']) ? $snap['form'] : [];
            $emisionNumeracionId = $e->numeracion_comprobante_id !== null
                ? (string) $e->numeracion_comprobante_id
                : (string) ($form['numeracionId'] ?? '');
            if ($numeracionId !== null && $numeracionId !== '' && $emisionNumeracionId !== (string) $numeracionId) {
                return false;
            }

            return true;
        });

        $nrosCuenta = $filtradas->pluck('nro_cuenta')->map(fn ($n) => trim((string) $n))->filter()->unique()->values()->all();
        $cuentasPorNro = Cuenta::query()
            ->whereIn('nro_cuenta', $nrosCuenta)
            ->with('paciente')
            ->get()
            ->keyBy(fn (Cuenta $c) => trim((string) $c->nro_cuenta));

        $idsCita = [];
        $idsHosp = [];
        $idsEmerg = [];
        foreach ($cuentasPorNro as $cu) {
            $orig = (string) ($cu->origen ?? '');
            $oid = (int) ($cu->origen_id ?? 0);
            if ($oid <= 0) {
                continue;
            }
            if ($orig === CuentaOrigen::CITA_ATENCION->value) {
                $idsCita[$oid] = true;
            } elseif ($orig === CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value) {
                $idsHosp[$oid] = true;
            } elseif ($orig === CuentaOrigen::REGISTRO_EMERGENCIA->value) {
                $idsEmerg[$oid] = true;
            }
        }

        $citasPorId = $idsCita === []
            ? collect()
            : CitaAtencion::query()
                ->whereIn('id', array_keys($idsCita))
                ->with(['agendaCita.programacion.medico'])
                ->get()
                ->keyBy('id');

        $hospPorId = $idsHosp === []
            ? collect()
            : PreFacturacionHospitalariaRegistro::query()
                ->whereIn('id', array_keys($idsHosp))
                ->get()
                ->keyBy('id');

        $emergPorId = $idsEmerg === []
            ? collect()
            : RegistroEmergencia::query()
                ->whereIn('id', array_keys($idsEmerg))
                ->with(['servicios' => function ($q) {
                    $q->orderBy('id')->with('medico');
                }])
                ->get()
                ->keyBy('id');

        $medioIdsSinPago = [];
        foreach ($filtradas as $ePre) {
            if ($ePre->pagos->isNotEmpty()) {
                continue;
            }
            $snapPre = is_array($ePre->snapshot) ? $ePre->snapshot : [];
            $formPre = isset($snapPre['form']) && is_array($snapPre['form']) ? $snapPre['form'] : [];
            $midPre = (int) ($formPre['medioPagoId'] ?? 0);
            if ($midPre > 0) {
                $medioIdsSinPago[$midPre] = true;
            }
        }
        $mediosPorId = $medioIdsSinPago === []
            ? collect()
            : CajaMedioPago::query()
                ->whereIn('id', array_keys($medioIdsSinPago))
                ->get()
                ->keyBy('id');

        $totalesPorMedio = [];
        $sumFacturas = 0.0;
        $sumBoletas = 0.0;
        $sumRecibo = 0.0;
        $movimientos = [];

        foreach ($filtradas as $e) {
            $snap = is_array($e->snapshot) ? $e->snapshot : [];
            $form = isset($snap['form']) && is_array($snap['form']) ? $snap['form'] : [];
            $labels = isset($snap['labels']) && is_array($snap['labels']) ? $snap['labels'] : [];
            $adelantoSnap = isset($snap['adelanto']) && is_array($snap['adelanto']) ? $snap['adelanto'] : [];
            $esGarantiaAdelanto = (bool) ($adelantoSnap['enabled'] ?? false);
            $montoGarantia = $esGarantiaAdelanto
                ? round((float) ($adelantoSnap['monto_con_igv'] ?? 0), 2)
                : 0.0;

            $emisionTotal = 0.0;
            if ($e->pagos->isNotEmpty()) {
                foreach ($e->pagos as $pagoRowSum) {
                    $emisionTotal += (float) ($pagoRowSum->monto ?? 0);
                }
            } else {
                $emisionTotal = $e->total_paciente !== null
                    ? (float) $e->total_paciente
                    : $this->totalDesdeSnapshot($snap);
            }
            $emisionTotal = round((float) $emisionTotal, 2);

            $tipoDocDesc = trim((string) ($e->numeracionComprobante?->tipoDocumento?->descripcion ?? ''));
            $tipoCompStr = $tipoDocDesc !== ''
                ? strtolower($tipoDocDesc)
                : strtolower((string) ($labels['tipo_comprobante'] ?? ''));
            if (str_contains($tipoCompStr, 'factura')) {
                $sumFacturas += $emisionTotal;
            } elseif (str_contains($tipoCompStr, 'boleta')) {
                $sumBoletas += $emisionTotal;
            } elseif (str_contains($tipoCompStr, 'recibo')) {
                $sumRecibo += $emisionTotal;
            }

            $nroTrim = trim((string) $e->nro_cuenta);
            $cuenta = $cuentasPorNro->get($nroTrim);

            $pacienteNombre = trim((string) ($form['paciente'] ?? ''));
            if ($cuenta !== null) {
                $pNom = trim((string) ($cuenta->paciente?->nombre_completo ?? ''));
                if ($pNom === '') {
                    $pNom = trim((string) ($cuenta->paciente_nombre ?? ''));
                }
                if ($pNom !== '') {
                    $pacienteNombre = $pNom;
                }
            }

            $medicoTratante = '';
            if ($cuenta !== null) {
                $medicoTratante = $this->resolverMedicoTratante(
                    $cuenta,
                    $citasPorId,
                    $hospPorId,
                    $emergPorId
                );
            }

            $tipoComprobanteLabel = $tipoDocDesc !== ''
                ? $tipoDocDesc
                : (string) ($labels['tipo_comprobante'] ?? '');

            $origenCuenta = (string) ($cuenta?->origen ?? $e->cuenta_origen ?? '');
            $origenSigla = $this->origenCuentaSigla($origenCuenta);

            $estadoCuenta = '';
            if ($cuenta !== null && $cuenta->estado !== null && (string) $cuenta->estado !== '') {
                $estadoCuenta = $this->estadoCuentaListado((string) $cuenta->estado);
            }
            if ($estadoCuenta === '') {
                $estadoCuenta = (string) ($labels['estado_emision'] ?? (string) ($form['estadoEmision'] ?? ''));
            }

            $baseRow = [
                'cuenta' => $this->nroCuentaSinCerosIzquierda($nroTrim !== '' ? $nroTrim : (string) $e->nro_cuenta),
                'paciente' => $pacienteNombre,
                'medico' => $medicoTratante !== '' ? $medicoTratante : '—',
                'tipo_comprobante' => $tipoComprobanteLabel,
                'num_comprobante' => $this->formatoSerieNumeroComprobante($e),
                'total' => number_format($emisionTotal, 2, '.', ''),
                'estado' => $estadoCuenta !== '' ? $estadoCuenta : '—',
                'origen_sigla' => $origenSigla !== '' ? $origenSigla : '—',
                'adelanto' => $esGarantiaAdelanto ? 'GARANTIA' : '—',
                'usuario_elimina' => '—',
            ];

            $pagosOrdenados = $e->pagos->sortBy('id')->values();
            $nPagos = $pagosOrdenados->count();
            $fraccionarLineasCtx = [];
            foreach ($pagosOrdenados as $pCtx) {
                $fraccionarLineasCtx[] = [
                    'forma_pago_id' => (int) $pCtx->forma_pago_id,
                    'medio_pago_id' => (int) $pCtx->medio_pago_id,
                    'banco_tarjeta_id' => $pCtx->banco_tarjeta_id !== null ? (int) $pCtx->banco_tarjeta_id : null,
                    'numero_operacion' => $pCtx->numero_operacion !== null && trim((string) $pCtx->numero_operacion) !== ''
                        ? trim((string) $pCtx->numero_operacion)
                        : null,
                    'fecha_vencimiento' => $pCtx->fecha_vencimiento?->format('Y-m-d'),
                    'monto' => number_format((float) ($pCtx->monto ?? 0), 2, '.', ''),
                ];
            }
            $formaIdSnap = (int) ($form['formaPagoId'] ?? 0);
            $medioIdSnap = (int) ($form['medioPagoId'] ?? 0);
            $bancoIdSnapRaw = isset($form['bancoTarjetaId']) ? (int) $form['bancoTarjetaId'] : 0;
            $bancoIdSnap = $bancoIdSnapRaw > 0 ? $bancoIdSnapRaw : null;
            $numOpSnap = isset($form['numeroOperacion']) ? trim((string) $form['numeroOperacion']) : '';

            $fraccionarCtxDefault = [
                'emision_total' => number_format($emisionTotal, 2, '.', ''),
                'lineas_pago' => $fraccionarLineasCtx,
            ];

            $fraccionarPermitido = $nPagos <= 2;

            if ($nPagos > 0) {
                foreach ($pagosOrdenados as $pago) {
                    $montoPago = round((float) ($pago->monto ?? 0), 2);
                    $medioPagoId = (int) ($pago->medio_pago_id ?? 0);
                    if ($medioPagoId > 0) {
                        $keyMp = (string) $medioPagoId;
                        $totalesPorMedio[$keyMp] = ($totalesPorMedio[$keyMp] ?? 0.0) + $montoPago;
                        if ($esGarantiaAdelanto && $montoGarantia > 0) {
                            $totalesPorMedio[$keyMp] = max(0.0, ($totalesPorMedio[$keyMp] ?? 0.0) - min($montoGarantia, $montoPago));
                            $totalesPorMedio['-1'] = ($totalesPorMedio['-1'] ?? 0.0) + min($montoGarantia, $montoPago);
                        }
                    }
                    $ml = '';
                    if ($pago->medioPago) {
                        $ml = trim((string) ($pago->medioPago->descripcion ?? ''));
                    }

                    $movimientos[] = array_merge($baseRow, [
                        'id' => (string) $e->id.'-'.(string) $pago->id,
                        'emision_comprobante_id' => (string) $e->id,
                        'linea_pago_id' => (string) $pago->id,
                        'pagos_en_emision' => $nPagos,
                        'fraccionar_permitido' => $fraccionarPermitido,
                        'fraccionar_context' => $fraccionarCtxDefault,
                        'pago_fracc' => number_format($montoPago, 2, '.', ''),
                        'medio_pago' => $ml !== '' ? $ml : '—',
                    ]);
                }
            } else {
                $medioLabel = '';
                if ($medioIdSnap > 0) {
                    $totalesPorMedio[(string) $medioIdSnap] = ($totalesPorMedio[(string) $medioIdSnap] ?? 0.0) + $emisionTotal;
                    if ($esGarantiaAdelanto && $montoGarantia > 0) {
                        $totalesPorMedio[(string) $medioIdSnap] = max(
                            0.0,
                            ($totalesPorMedio[(string) $medioIdSnap] ?? 0.0) - min($montoGarantia, $emisionTotal)
                        );
                        $totalesPorMedio['-1'] = ($totalesPorMedio['-1'] ?? 0.0) + min($montoGarantia, $emisionTotal);
                    }
                    $mForm = $mediosPorId->get($medioIdSnap);
                    if ($mForm) {
                        $medioLabel = trim((string) ($mForm->descripcion ?? ''));
                    }
                }
                if ($medioLabel === '') {
                    $medioLabel = $this->medioNombreDesdeEtiquetaCompuesta((string) ($labels['medio_pago'] ?? ''));
                }

                $fechaSnap = $e->fecha_vencimiento !== null ? $e->fecha_vencimiento->format('Y-m-d') : null;
                $fraccionarCtxSinTabla = $formaIdSnap > 0 && $medioIdSnap > 0
                    ? [
                        'emision_total' => number_format($emisionTotal, 2, '.', ''),
                        'lineas_pago' => [[
                            'forma_pago_id' => $formaIdSnap,
                            'medio_pago_id' => $medioIdSnap,
                            'banco_tarjeta_id' => $bancoIdSnap,
                            'numero_operacion' => $numOpSnap !== '' ? $numOpSnap : null,
                            'fecha_vencimiento' => $fechaSnap,
                            'monto' => number_format($emisionTotal, 2, '.', ''),
                        ]],
                    ]
                    : $fraccionarCtxDefault;

                $movimientos[] = array_merge($baseRow, [
                    'id' => (string) $e->id.'-0',
                    'emision_comprobante_id' => (string) $e->id,
                    'linea_pago_id' => null,
                    'pagos_en_emision' => 0,
                    'fraccionar_permitido' => true,
                    'fraccionar_context' => $fraccionarCtxSinTabla,
                    'pago_fracc' => '—',
                    'medio_pago' => $medioLabel !== '' ? $medioLabel : '—',
                ]);
            }
        }

        $totalesPorMedioFmt = [];
        foreach ($totalesPorMedio as $k => $v) {
            $totalesPorMedioFmt[$k] = number_format((float) $v, 2, '.', '');
        }

        $totalGeneral = array_sum(array_map(fn ($v) => (float) $v, $totalesPorMedio));

        $totalFilas = count($movimientos);
        $perPage = max(1, min($perPage, 100));
        $lastPage = max(1, (int) ceil($totalFilas / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;
        $movimientosPagina = array_slice($movimientos, $offset, $perPage);

        return [
            'movimientos' => $movimientosPagina,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalFilas,
                'last_page' => $lastPage,
            ],
            'totales_por_medio' => $totalesPorMedioFmt,
            'totales_documento' => [
                'facturas' => number_format($sumFacturas, 2, '.', ''),
                'boletas' => number_format($sumBoletas, 2, '.', ''),
                'recibo_caja' => number_format($sumRecibo, 2, '.', ''),
            ],
            'total_general' => number_format($totalGeneral, 2, '.', ''),
        ];
    }

    private function medioNombreDesdeEtiquetaCompuesta(string $raw): string
    {
        $t = trim($raw);
        if ($t === '') {
            return '';
        }
        if (! str_contains($t, '·')) {
            return $t;
        }
        $parts = array_map('trim', explode('·', $t));
        $last = end($parts);

        return ($last !== false && $last !== '') ? $last : $t;
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

    private function nroCuentaSinCerosIzquierda(string $nro): string
    {
        $t = ltrim($nro, '0');

        return $t === '' ? '0' : $t;
    }

    private function formatoSerieNumeroComprobante(CajaEmisionComprobante $e): string
    {
        $serie = trim((string) ($e->serie ?? ''));
        if ($serie === '' && $e->relationLoaded('numeracionComprobante') && $e->numeracionComprobante) {
            $serie = trim((string) ($e->numeracionComprobante->serie ?? ''));
        }
        $num = $e->numero_emitido;
        if ($num === null) {
            $snap = is_array($e->snapshot) ? $e->snapshot : [];
            $form = isset($snap['form']) && is_array($snap['form']) ? $snap['form'] : [];
            $cor = trim((string) ($form['correlativo'] ?? ''));
            if ($cor !== '' && preg_match('/^0*(\d+)$/', $cor, $m)) {
                $num = (int) $m[1];
            }
        }
        if ($num === null) {
            return $serie !== '' ? $serie : '—';
        }
        $n = (string) max(0, (int) $num);
        if ($serie === '') {
            return $n;
        }

        return $serie.' - '.$n;
    }

    private function origenCuentaSigla(string $origen): string
    {
        return match ($origen) {
            CuentaOrigen::CITA_ATENCION->value => 'AMB',
            CuentaOrigen::REGISTRO_EMERGENCIA->value => 'EM',
            CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value => 'HOS',
            default => $origen,
        };
    }

    private function estadoCuentaListado(string $estado): string
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

    private function resolverMedicoTratante(
        Cuenta $cuenta,
        Collection $citasPorId,
        Collection $hospPorId,
        Collection $emergPorId
    ): string {
        $origen = (string) ($cuenta->origen ?? '');

        if ($origen === CuentaOrigen::CITA_ATENCION->value) {
            $ca = $citasPorId->get((int) $cuenta->origen_id);
            if ($ca) {
                $m = $ca->agendaCita?->programacion?->medico;
                if ($m) {
                    $nom = trim((string) ($m->nombre_completo ?? ''));
                    if ($nom !== '') {
                        return $nom;
                    }
                }
            }

            return '';
        }

        if ($origen === CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value) {
            $reg = $hospPorId->get((int) $cuenta->origen_id);
            if ($reg && is_array($reg->payload)) {
                return $this->medicoDesdePayloadHospital($reg->payload);
            }

            return '';
        }

        if ($origen === CuentaOrigen::REGISTRO_EMERGENCIA->value) {
            $re = $emergPorId->get((int) $cuenta->origen_id);
            if ($re) {
                return $this->medicoDesdeEmergencia($re);
            }

            return '';
        }

        return '';
    }

    private function medicoDesdePayloadHospital(array $payload): string
    {
        return trim((string) ($payload['medicoTratanteNombre'] ?? ''));
    }

    private function medicoDesdeEmergencia(RegistroEmergencia $re): string
    {
        foreach ($re->servicios as $s) {
            if ($s->medico_id && $s->relationLoaded('medico') && $s->medico) {
                $nom = trim((string) ($s->medico->nombre_completo ?? ''));
                if ($nom !== '') {
                    return $nom;
                }
            }
        }

        $parts = array_values(array_filter([
            trim((string) ($re->medico_emergencia ?? '')),
            trim((string) ($re->medico_especialista ?? '')),
        ], fn ($x) => $x !== ''));

        return $parts !== [] ? implode(' · ', $parts) : '';
    }
}
