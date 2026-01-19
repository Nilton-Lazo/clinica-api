<?php

namespace App\Core\support;

enum SexoPaciente: string
{
    case MASCULINO = 'MASCULINO';
    case FEMENINO = 'FEMENINO';

    public static function values(): array
    {
        return array_map(fn(self $x) => $x->value, self::cases());
    }
}
