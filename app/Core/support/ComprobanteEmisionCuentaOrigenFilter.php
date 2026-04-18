<?php

namespace App\Core\support;

final class ComprobanteEmisionCuentaOrigenFilter
{
    public static function cuentaOriginesFor(?string $emisionOrigen): ?array
    {
        $v = trim((string) $emisionOrigen);
        if ($v === '') {
            return null;
        }

        foreach (ComprobanteEmisionOrigen::cases() as $case) {
            if ($case->value === $v) {
                return self::forCase($case);
            }
        }

        return null;
    }

    private static function forCase(ComprobanteEmisionOrigen $o): ?array
    {
        return match ($o) {
            ComprobanteEmisionOrigen::CONSULTA_AMBULATORIA => [CuentaOrigen::CITA_ATENCION->value],
            ComprobanteEmisionOrigen::HOSPITALIZACION => [
                CuentaOrigen::REGISTRO_EMERGENCIA->value,
                CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value,
            ],
            ComprobanteEmisionOrigen::PRESTACIONES => [],
        };
    }
}
