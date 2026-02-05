<?php

namespace App\Core\support;

enum CitaAtencionEstado: string
{
    case PENDIENTE = 'PENDIENTE';
    case ATENDIDO = 'ATENDIDO';

    public static function values(): array
    {
        return array_map(fn(self $e) => $e->value, self::cases());
    }
}
