<?php

namespace App\Core\support;

enum SexoPaciente: string
{
    case MASCULINO = 'MASCULINO';
    case FEMENINO = 'FEMENINO';

    public static function values(): array
    {
        return array_map(fn(self $x) => $x->value, self::cases());
    }

    /**
     * Formato para mostrar en UI: primera letra mayúscula (ej. "Masculino", "Femenino").
     */
    public static function formatForDisplay(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $v = strtoupper(trim($value));
        return match ($v) {
            'MASCULINO' => 'Masculino',
            'FEMENINO' => 'Femenino',
            default => \Illuminate\Support\Str::title(strtolower($value)),
        };
    }
}
