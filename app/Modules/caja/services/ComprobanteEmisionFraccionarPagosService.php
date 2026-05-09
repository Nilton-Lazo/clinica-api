<?php

namespace App\Modules\caja\services;

use App\Core\realtime\RealtimeBroadcaster;
use App\Models\User;
use App\Modules\admision\models\CajaBancoTarjeta;
use App\Modules\admision\models\CajaFormaPago;
use App\Modules\admision\models\CajaMedioPago;
use App\Modules\caja\models\CajaEmisionComprobante;
use App\Modules\caja\models\CajaEmisionComprobantePago;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComprobanteEmisionFraccionarPagosService
{
    public function __construct(
        private RealtimeBroadcaster $realtime,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $pagos
     */
    public function ejecutar(User $actor, int $emisionComprobanteId, array $pagos): void
    {
        if (count($pagos) !== 2) {
            throw ValidationException::withMessages([
                'pagos' => ['Debes registrar exactamente dos líneas de pago para fraccionar el comprobante.'],
            ]);
        }

        DB::transaction(function () use ($actor, $emisionComprobanteId, $pagos): void {
            $e = CajaEmisionComprobante::query()
                ->lockForUpdate()
                ->with([
                    'cajaApertura:id,user_recepciona_id',
                ])
                ->find($emisionComprobanteId);
            if (! $e) {
                throw ValidationException::withMessages([
                    'emision_comprobante_id' => ['El comprobante seleccionado no existe. Actualiza el reporte e inténtalo de nuevo.'],
                ]);
            }
            $apertura = $e->cajaApertura;
            if (! $apertura || (int) $apertura->user_recepciona_id !== (int) $actor->id) {
                throw ValidationException::withMessages([
                    'emision_comprobante_id' => ['No tienes permiso para modificar los pagos de este comprobante.'],
                ]);
            }

            $actuales = CajaEmisionComprobantePago::query()
                ->where('emision_comprobante_id', (int) $e->id)
                ->lockForUpdate()
                ->get(['id'])
                ->count();
            if ($actuales > 2) {
                throw ValidationException::withMessages([
                    'pagos' => ['Este comprobante tiene más de dos pagos registrados. Contacte a soporte para ajustarlo.'],
                ]);
            }

            $totalEsperado = round((float) ($e->total_paciente ?? 0), 2);
            $sumMontos = 0.0;
            $creados = [];

            foreach ($pagos as $idx => $p) {
                $sub = isset($p['forma_pago_id']) ? (int) $p['forma_pago_id'] : 0;
                $med = isset($p['medio_pago_id']) ? (int) $p['medio_pago_id'] : 0;
                $banco = isset($p['banco_tarjeta_id']) ? (int) $p['banco_tarjeta_id'] : null;
                $banco = $banco !== null && $banco > 0 ? $banco : null;
                $numOp = isset($p['numero_operacion']) ? trim((string) $p['numero_operacion']) : '';
                $numOp = $numOp === '' ? null : $numOp;
                $fechaVenRaw = isset($p['fecha_vencimiento']) ? trim((string) $p['fecha_vencimiento']) : '';
                $fechaVen = $fechaVenRaw === '' ? null : $fechaVenRaw;
                $monto = isset($p['monto']) ? round((float) $p['monto'], 2) : null;

                if ($monto === null || $monto < 0) {
                    throw ValidationException::withMessages([
                        'pagos' => ['Cada línea de pago debe tener un monto válido igual o mayor que cero.'],
                    ]);
                }

                $formaPago = CajaFormaPago::query()
                    ->activos()
                    ->whereKey($sub)
                    ->first();
                if (! $formaPago) {
                    throw ValidationException::withMessages([
                        "pagos.{$idx}.forma_pago_id" => ['La forma de pago de la línea '.($idx + 1).' no está activa.'],
                    ]);
                }

                $medioPago = CajaMedioPago::query()
                    ->activos()
                    ->whereKey($med)
                    ->whereHas('formasPago', fn ($q) => $q->whereKey($sub))
                    ->first();
                if (! $medioPago) {
                    throw ValidationException::withMessages([
                        "pagos.{$idx}.medio_pago_id" => ['El medio de pago de la línea '.($idx + 1).' no está activo o no coincide con la forma de pago.'],
                    ]);
                }

                $bancosCompatiblesQuery = CajaBancoTarjeta::query()
                    ->activos()
                    ->whereHas('formasPago', fn ($q) => $q->whereKey($sub))
                    ->whereHas('mediosPago', fn ($q) => $q->whereKey($med));
                $hayBancosCompatibles = $bancosCompatiblesQuery->exists();

                $bancoTarjetaId = null;
                if ($banco !== null) {
                    $bSel = (clone $bancosCompatiblesQuery)->whereKey($banco)->first();
                    if (! $bSel) {
                        throw ValidationException::withMessages([
                            "pagos.{$idx}.banco_tarjeta_id" => ['El banco o tarjeta de la línea '.($idx + 1).' no es válido para la combinación seleccionada.'],
                        ]);
                    }
                    $bancoTarjetaId = $banco;
                } elseif ($hayBancosCompatibles) {
                    throw ValidationException::withMessages([
                        "pagos.{$idx}.banco_tarjeta_id" => ['Selecciona banco o tarjeta en la línea de pago '.($idx + 1).'.'],
                    ]);
                }

                $codigoForma = trim((string) ($formaPago->codigo ?? ''));
                if ($codigoForma === '002') {
                    if ($fechaVen === null) {
                        throw ValidationException::withMessages([
                            "pagos.{$idx}.fecha_vencimiento" => ['Indica la fecha de vencimiento en la línea de pago '.($idx + 1).' (forma crédito).'],
                        ]);
                    }
                } elseif ($fechaVen !== null) {
                    throw ValidationException::withMessages([
                        "pagos.{$idx}.fecha_vencimiento" => ['La fecha de vencimiento solo aplica cuando la forma de pago es crédito (línea '.($idx + 1).').'],
                    ]);
                }

                $sumMontos += $monto;
                $creados[] = [
                    'emision_comprobante_id' => (int) $e->id,
                    'forma_pago_id' => (int) $formaPago->id,
                    'medio_pago_id' => (int) $medioPago->id,
                    'banco_tarjeta_id' => $bancoTarjetaId,
                    'numero_operacion' => $numOp,
                    'monto' => $monto,
                    'fecha_vencimiento' => $fechaVen,
                ];
            }

            if (abs(round($sumMontos, 2) - $totalEsperado) > 0.02) {
                throw ValidationException::withMessages([
                    'pagos' => ['La suma de los dos montos debe ser exactamente '.$totalEsperado.' PEN (total del comprobante). Actualmente suma '.round($sumMontos, 2).'.'],
                ]);
            }

            CajaEmisionComprobantePago::query()
                ->where('emision_comprobante_id', (int) $e->id)
                ->delete();

            foreach ($creados as $row) {
                CajaEmisionComprobantePago::create($row);
            }

            $fechaEmision = null;
            foreach ($creados as $row) {
                if (! empty($row['fecha_vencimiento'])) {
                    $fechaEmision = $row['fecha_vencimiento'];
                    break;
                }
            }
            $e->fecha_vencimiento = $fechaEmision;
            $e->save();

            $this->realtime->entityChanged(
                module: 'caja',
                entity: 'emision_comprobante',
                action: 'fraccionar_pagos',
                id: (int) $e->id,
                scope: trim((string) $e->nro_cuenta),
                metadata: [
                    'nro_cuenta' => (string) $e->nro_cuenta,
                    'caja_apertura_id' => (int) $e->caja_apertura_id,
                    'pagos_lineas' => 2,
                ],
                actorId: (int) $actor->id,
            );
        });
    }
}
