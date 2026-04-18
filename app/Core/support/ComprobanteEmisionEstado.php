<?php

namespace App\Core\support;

enum ComprobanteEmisionEstado: string
{
    case PENDIENTE = 'PENDIENTE';
    case EMITIDO = 'EMITIDO';
    case ANULADO = 'ANULADO';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::EMITIDO => 'Emitido',
            self::ANULADO => 'Anulado',
        };
    }
}
