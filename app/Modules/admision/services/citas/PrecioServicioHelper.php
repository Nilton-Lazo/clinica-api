<?php

namespace App\Modules\admision\services\citas;

use App\Modules\admision\models\ParametroSistema;

final class PrecioServicioHelper
{
    private const TARIFAS_PRECIO_DIRECTO = ['Particular', 'Privado'];

    public static function esPrecioDirecto(?string $descripcionTarifa): bool
    {
        if ($descripcionTarifa === null || $descripcionTarifa === '') {
            return false;
        }
        $norm = trim($descripcionTarifa);
        foreach (self::TARIFAS_PRECIO_DIRECTO as $t) {
            if (strcasecmp($norm, $t) === 0) {
                return true;
            }
        }
        return false;
    }

    public static function calcular(
        float $precioBaseSinIgv,
        float $cantidad,
        float $descuentoPct = 0,
        float $aumentoPct = 0
    ): array {
        $subtotal = $precioBaseSinIgv * max(0, $cantidad);
        if ($descuentoPct > 0) {
            $subtotal *= 1 - ($descuentoPct / 100);
        }
        if ($aumentoPct > 0) {
            $subtotal *= 1 + ($aumentoPct / 100);
        }
        $precioSinIgv = round($subtotal, 3);

        $igvPct = ParametroSistema::getIgvPorcentaje();
        $igv = $precioSinIgv * ($igvPct / 100);
        $precioConIgv = round($precioSinIgv + $igv, 3);

        return [
            'precio_sin_igv' => $precioSinIgv,
            'precio_con_igv' => $precioConIgv,
        ];
    }
}
