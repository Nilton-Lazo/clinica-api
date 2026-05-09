<?php

namespace App\Modules\caja\support;

use App\Modules\caja\models\CajaEmisionComprobante;

final class EmisionComprobanteFacturacion
{
    public static function esAdelantoGarantia(CajaEmisionComprobante $emision): bool
    {
        $snapshot = is_array($emision->snapshot) ? $emision->snapshot : [];
        $adelanto = isset($snapshot['adelanto']) && is_array($snapshot['adelanto']) ? $snapshot['adelanto'] : [];

        return (bool) ($adelanto['enabled'] ?? false);
    }

    public static function existeFacturadoraParaCuenta(string $nroCuenta): bool
    {
        return self::primeraFacturadoraParaCuenta($nroCuenta) !== null;
    }

    public static function primeraFacturadoraParaCuenta(string $nroCuenta): ?CajaEmisionComprobante
    {
        $nro = trim($nroCuenta);
        if ($nro === '') {
            return null;
        }

        $emisiones = CajaEmisionComprobante::query()
            ->where('nro_cuenta', $nro)
            ->orderBy('id')
            ->get(['id', 'nro_cuenta', 'numeracion_comprobante_id', 'serie', 'numero_emitido', 'snapshot']);

        foreach ($emisiones as $emision) {
            if (! self::esAdelantoGarantia($emision)) {
                return $emision;
            }
        }

        return null;
    }

    public static function totalAdelantoGarantiaPorCuenta(string $nroCuenta): float
    {
        $nro = trim($nroCuenta);
        if ($nro === '') {
            return 0.0;
        }

        $emisiones = CajaEmisionComprobante::query()
            ->where('nro_cuenta', $nro)
            ->orderBy('id')
            ->get(['snapshot']);

        $total = 0.0;
        foreach ($emisiones as $emision) {
            if (! self::esAdelantoGarantia($emision)) {
                continue;
            }
            $snapshot = is_array($emision->snapshot) ? $emision->snapshot : [];
            $adelanto = isset($snapshot['adelanto']) && is_array($snapshot['adelanto']) ? $snapshot['adelanto'] : [];
            $monto = isset($adelanto['monto_con_igv']) ? (float) $adelanto['monto_con_igv'] : 0.0;
            if ($monto > 0) {
                $total += $monto;
            }
        }

        return round($total, 2);
    }
}
