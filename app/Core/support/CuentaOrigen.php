<?php

namespace App\Core\support;

enum CuentaOrigen: string
{
    case REGISTRO_EMERGENCIA = 'REGISTRO_EMERGENCIA';
    case CITA_ATENCION = 'CITA_ATENCION';
    case PRE_FACTURACION_HOSPITALARIA = 'PRE_FACTURACION_HOSPITALARIA';

    public static function values(): array
    {
        return array_map(fn (self $x) => $x->value, self::cases());
    }
}
