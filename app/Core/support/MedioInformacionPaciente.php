<?php

namespace App\Core\support;

enum MedioInformacionPaciente: string
{
    case PAGINA_WEB = 'PAGINA_WEB';
    case TV_RADIO = 'TV_RADIO';
    case FACEBOOK = 'FACEBOOK';
    case INSTAGRAM = 'INSTAGRAM';
    case X = 'X';
    case TIK_TOK = 'TIK_TOK';
    case RECOMENDACION = 'RECOMENDACION';
    case MEDICO = 'MEDICO';
    case OTRO = 'OTRO';

    public static function values(): array
    {
        return array_map(fn(self $x) => $x->value, self::cases());
    }
}
