<?php

namespace App\Core\support;

enum TipoProfesionalClinica: string
{
    case STAFF = 'STAFF';
    case EXTERNO = 'EXTERNO';

    public static function values(): array
    {
        return array_map(fn(self $t) => $t->value, self::cases());
    }
}
