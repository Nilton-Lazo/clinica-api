<?php

namespace App\Modules\ficheros\support;

final class TarifaServicioPrecioIgv
{
    public static function precioConIgvDesdeSin(float|string $precioSin, float $igvPorcentaje): string
    {
        $sin = (float) $precioSin;
        $factor = 1.0 + ($igvPorcentaje / 100.0);

        return (string) round($sin * $factor, 4);
    }
}
