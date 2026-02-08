<?php

namespace App\Modules\admision\services\citas;

use App\Modules\admision\models\ParametroSistema;

/**
 * Calcula precio_sin_igv y precio_con_igv según tipo de tarifa.
 * Particular/Privado: usa precio_sin_igv del servicio.
 * Resto: lógica futura (dejar preparado).
 */
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

    /**
     * @param float $precioBaseSinIgv Precio base sin IGV del servicio (ej. tarifa_servicio.precio_sin_igv)
     * @param float $cantidad
     * @param float $descuentoPct 0-100
     * @param float $aumentoPct 0-100
     * @return array{precio_sin_igv: float, precio_con_igv: float}
     */
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
