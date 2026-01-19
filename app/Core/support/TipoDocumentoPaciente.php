<?php

namespace App\Core\support;

enum TipoDocumentoPaciente: string
{
    case SIN_DOCUMENTO = 'SIN_DOCUMENTO';
    case DNI = 'DNI';
    case CE = 'CE';
    case RUC = 'RUC';
    case CIP = 'CIP';
    case PASAPORTE = 'PASAPORTE';

    public static function values(): array
    {
        return array_map(fn(self $t) => $t->value, self::cases());
    }
}
