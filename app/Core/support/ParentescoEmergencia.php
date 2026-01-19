<?php

namespace App\Core\support;

enum ParentescoEmergencia: string
{
    case CONYUGE = 'CONYUGE';
    case HIJO_A = 'HIJO_A';
    case CONCUBINO = 'CONCUBINO';
    case MADRE = 'MADRE';
    case PADRE = 'PADRE';
    case HERMANO_A = 'HERMANO_A';
    case TIO_A = 'TIO_A';
    case PRIMO_A = 'PRIMO_A';
    case AMIGO_A = 'AMIGO_A';
    case SUEGRO_A = 'SUEGRO_A';
    case CUNADO_A = 'CUNADO_A';
    case SOBRINO_A = 'SOBRINO_A';
    case DOCTOR_A = 'DOCTOR_A';
    case NIETO_A = 'NIETO_A';
    case APODERADO_A = 'APODERADO_A';
    case CONOCIDO_A = 'CONOCIDO_A';

    public static function values(): array
    {
        return array_map(fn(self $x) => $x->value, self::cases());
    }
}
