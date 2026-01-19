<?php

namespace App\Core\support;

enum ParentescoSeguroPaciente: string
{
    case NO_DEFINIDO = 'NO_DEFINIDO';
    case TITULAR = 'TITULAR';
    case CONYUGE = 'CONYUGE';
    case PADRE = 'PADRE';
    case MADRE = 'MADRE';
    case HIJO = 'HIJO';
    case HIJA = 'HIJA';
    case HERMANO = 'HERMANO';
    case HERMANA = 'HERMANA';
    case HIJO_INCAPACITADO = 'HIJO_INCAPACITADO';

    public static function values(): array
    {
        return array_map(fn(self $x) => $x->value, self::cases());
    }
}
