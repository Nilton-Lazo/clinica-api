<?php

namespace App\Core\support;

enum OcupacionPaciente: string
{
    case NO_DEFINIDO = 'NO_DEFINIDO';
    case AMA_DE_CASA = 'AMA_DE_CASA';
    case EMPLEADO = 'EMPLEADO';
    case INDEPENDIENTE = 'INDEPENDIENTE';

    public static function values(): array
    {
        return array_map(fn(self $x) => $x->value, self::cases());
    }
}
