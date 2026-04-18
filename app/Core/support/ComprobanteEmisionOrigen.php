<?php

namespace App\Core\support;

enum ComprobanteEmisionOrigen: string
{
    case CONSULTA_AMBULATORIA = 'CONSULTA_AMBULATORIA';
    case HOSPITALIZACION = 'HOSPITALIZACION';
    case PRESTACIONES = 'PRESTACIONES';

    public function label(): string
    {
        return match ($this) {
            self::CONSULTA_AMBULATORIA => 'Consulta ambulatoria',
            self::HOSPITALIZACION => 'Hospitalización',
            self::PRESTACIONES => 'Prestaciones',
        };
    }
}
