<?php

namespace App\Core\support;

enum ModalidadFechasProgramacion: string
{
    case DIARIA = 'DIARIA';
    case ALEATORIA = 'ALEATORIA';
    case RANGO = 'RANGO';

    public static function values(): array
    {
        return array_map(fn(self $m) => $m->value, self::cases());
    }
}
