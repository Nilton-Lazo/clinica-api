<?php

namespace App\Core\support;

enum PreFacturacionHospitalariaTipo: string
{
    case HOSPITALIZACION = 'HOSPITALIZACION';
    case CENTRO_QUIRURGICO = 'CENTRO_QUIRURGICO';
    case UCI_UCIN = 'UCI_UCIN';

    public static function values(): array
    {
        return array_map(fn(self $x) => $x->value, self::cases());
    }
}
