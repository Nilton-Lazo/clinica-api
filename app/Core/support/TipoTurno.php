<?php

namespace App\Core\support;

enum TipoTurno: string
{
    case NORMAL = 'NORMAL';
    case ADICIONAL = 'ADICIONAL';
    case EXCLUSIVO = 'EXCLUSIVO';

    public static function values(): array
    {
        return array_map(fn(self $t) => $t->value, self::cases());
    }
}
