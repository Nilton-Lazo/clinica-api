<?php

namespace App\Core\support;

use Illuminate\Validation\ValidationException;

final class SequentialNumericCode
{
    public static function format(int $sequence, ?int $minWidth = null, ?int $maxWidth = null): string
    {
        if ($sequence < 1) {
            throw new \InvalidArgumentException('El correlativo numérico debe ser mayor o igual a 1.');
        }

        $minWidth = $minWidth ?? CodigoCorrelativo::minDigits();

        $digitCount = strlen((string) $sequence);
        $width = max($minWidth, $digitCount);

        if ($maxWidth !== null) {
            if ($digitCount > $maxWidth) {
                throw ValidationException::withMessages([
                    'codigo' => ["No se pudo generar el código: supera el máximo de {$maxWidth} dígitos permitidos."],
                ]);
            }
            $width = min($width, $maxWidth);
        }

        return str_pad((string) $sequence, $width, '0', STR_PAD_LEFT);
    }

    public static function parseLast(?string $codigo): int
    {
        $raw = trim((string) $codigo);
        if ($raw === '' || !preg_match('/^\d+$/', $raw)) {
            return 0;
        }

        return max(0, (int) $raw);
    }

    public static function nextFromLast(?string $lastCodigo, ?int $minWidth = null, ?int $maxWidth = null): string
    {
        return self::format(self::parseLast($lastCodigo) + 1, $minWidth, $maxWidth);
    }

    public static function guardMaxLength(string $codigo, int $maxLength): void
    {
        if (strlen($codigo) > $maxLength) {
            throw ValidationException::withMessages([
                'codigo' => ["No se pudo generar el código: supera {$maxLength} caracteres."],
            ]);
        }
    }
}
