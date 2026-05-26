<?php

namespace App\Core\reporting;

final class ReportDisplayFormatter
{
    public static function enumLabel(?string $value): string
    {
        $t = trim((string) $value);
        if ($t === '') {
            return '—';
        }

        $lower = strtolower(str_replace('_', ' ', $t));

        return mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8');
    }

    public static function text(?string $value): string
    {
        $t = trim((string) $value);

        return $t !== '' ? $t : '—';
    }

    public static function joinPhone(?string $celular, ?string $telefono): string
    {
        $parts = array_values(array_filter([
            trim((string) $celular),
            trim((string) $telefono),
        ], fn ($x) => $x !== ''));

        return $parts !== [] ? implode(' / ', $parts) : '—';
    }

    public static function fullName(?string $apellidoPaterno, ?string $apellidoMaterno, ?string $nombres): string
    {
        $parts = array_values(array_filter([
            trim((string) $apellidoPaterno),
            trim((string) $apellidoMaterno),
            trim((string) $nombres),
        ], fn ($x) => $x !== ''));

        return $parts !== [] ? implode(' ', $parts) : '—';
    }
}
