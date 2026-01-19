<?php

namespace App\Core\support;

enum TipoPaciente: string
{
    case PARTICULAR = 'PARTICULAR';
    case PRIVADO = 'PRIVADO';
    case CONVENIO = 'CONVENIO';

    public static function values(): array
    {
        return array_map(fn(self $x) => $x->value, self::cases());
    }
}
