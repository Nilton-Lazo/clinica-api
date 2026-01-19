<?php

namespace App\Core\support;

enum EstadoCivilPaciente: string
{
    case CASADO = 'CASADO';
    case SOLTERO = 'SOLTERO';
    case CONVIVIENTE = 'CONVIVIENTE';
    case DIVORCIADO = 'DIVORCIADO';
    case VIUDO = 'VIUDO';

    public static function values(): array
    {
        return array_map(fn(self $x) => $x->value, self::cases());
    }
}
