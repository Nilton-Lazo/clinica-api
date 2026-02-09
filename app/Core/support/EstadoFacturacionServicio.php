<?php

namespace App\Core\support;

enum EstadoFacturacionServicio: string
{
    case PENDIENTE = 'PENDIENTE';
    case FACTURADO = 'FACTURADO';

    public static function values(): array
    {
        return array_map(fn(self $e) => $e->value, self::cases());
    }
}
