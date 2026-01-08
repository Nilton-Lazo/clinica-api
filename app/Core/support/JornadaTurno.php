<?php

namespace App\Core\support;

enum JornadaTurno: string
{
    case MANANA = 'MANANA';
    case TARDE = 'TARDE';
    case NOCHE = 'NOCHE';

    public static function values(): array
    {
        return array_map(fn(self $j) => $j->value, self::cases());
    }
}
