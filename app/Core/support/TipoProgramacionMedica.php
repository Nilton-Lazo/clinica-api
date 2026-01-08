<?php

namespace App\Core\support;

enum TipoProgramacionMedica: string
{
    case NORMAL = 'NORMAL';
    case EXTRAORDINARIA = 'EXTRAORDINARIA';

    public static function values(): array
    {
        return array_map(fn(self $t) => $t->value, self::cases());
    }
}
