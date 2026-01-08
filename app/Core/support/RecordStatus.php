<?php

namespace App\Core\support;

enum RecordStatus: string
{
    case ACTIVO = 'ACTIVO';
    case INACTIVO = 'INACTIVO';
    case SUSPENDIDO = 'SUSPENDIDO';

    public static function values(): array
    {
        return array_map(fn(self $s) => $s->value, self::cases());
    }
}
