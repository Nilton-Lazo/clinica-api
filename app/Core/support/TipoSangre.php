<?php

namespace App\Core\support;

enum TipoSangre: string
{
    case A_POS = 'A+';
    case A_NEG = 'A-';
    case B_POS = 'B+';
    case B_NEG = 'B-';
    case AB_POS = 'AB+';
    case AB_NEG = 'AB-';
    case O_POS = 'O+';
    case O_NEG = 'O-';

    public static function values(): array
    {
        return array_map(fn(self $x) => $x->value, self::cases());
    }
}
