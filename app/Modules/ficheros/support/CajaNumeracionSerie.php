<?php

namespace App\Modules\ficheros\support;

use App\Core\support\CodigoCorrelativo;
use Illuminate\Validation\ValidationException;

final class CajaNumeracionSerie
{
    public static function maxValue(): int
    {
        $maxDigits = CodigoCorrelativo::profileMaxDigits('serie_numeracion') ?? 3;
        $limit = (int) str_repeat('9', $maxDigits);

        return $limit > 0 ? $limit : 999;
    }

    public static function normalize(?string $value): string
    {
        $digits = preg_replace('/\D/', '', trim((string) $value));
        if ($digits === '') {
            throw ValidationException::withMessages([
                'serie' => 'Ingresa la serie numérica de la numeración (hasta 3 dígitos).',
            ]);
        }

        $num = (int) $digits;
        if ($num < 0 || $num > self::maxValue()) {
            throw ValidationException::withMessages([
                'serie' => 'La serie debe ser un número entre 0 y '.self::maxValue().'.',
            ]);
        }

        return CodigoCorrelativo::format($num, 'serie_numeracion');
    }

    public static function tryNormalize(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', trim((string) $value));
        if ($digits === '') {
            return null;
        }

        $num = min(self::maxValue(), max(0, (int) $digits));

        return CodigoCorrelativo::format($num, 'serie_numeracion');
    }
}
