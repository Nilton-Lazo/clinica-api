<?php

namespace App\Core\support;

final class CodigoCorrelativo
{
    public static function minDigits(): int
    {
        return max(1, (int) config('codigos.correlativo_min_digits', 3));
    }

    public static function maxDigits(): int
    {
        return max(self::minDigits(), (int) config('codigos.correlativo_max_digits', 10));
    }

    public static function profileMinDigits(string $profile): int
    {
        $profiles = config('codigos.profiles', []);
        $cfg = $profiles[$profile] ?? $profiles['maestro'] ?? [];

        return max(1, (int) ($cfg['min_digits'] ?? self::minDigits()));
    }

    public static function profileMaxDigits(string $profile): ?int
    {
        $profiles = config('codigos.profiles', []);
        $cfg = $profiles[$profile] ?? $profiles['maestro'] ?? [];
        $max = $cfg['max_digits'] ?? self::maxDigits();

        return $max !== null && (int) $max > 0 ? (int) $max : null;
    }

    public static function format(int $sequence, ?string $profile = null): string
    {
        $profileKey = $profile ?? 'maestro';

        return SequentialNumericCode::format(
            $sequence,
            self::profileMinDigits($profileKey),
            self::profileMaxDigits($profileKey),
        );
    }

    public static function nextFromLast(?string $lastCodigo, ?string $profile = null): string
    {
        $profileKey = $profile ?? 'maestro';

        return SequentialNumericCode::format(
            SequentialNumericCode::parseLast($lastCodigo) + 1,
            self::profileMinDigits($profileKey),
            self::profileMaxDigits($profileKey),
        );
    }

    public static function guardMaxLength(string $codigo, ?string $profile = null): void
    {
        $max = self::profileMaxDigits($profile ?? 'maestro');
        if ($max === null) {
            SequentialNumericCode::guardMaxLength($codigo, 50);

            return;
        }

        SequentialNumericCode::guardMaxLength($codigo, $max);
    }

    public static function publicConfig(): array
    {
        return [
            'correlativo_min_digits' => self::minDigits(),
            'correlativo_max_digits' => self::maxDigits(),
            'profiles' => config('codigos.profiles', []),
        ];
    }

    /**
     * Orden numérico de códigos (1, 2, …, 8, 10) en lugar de lexicográfico (008 antes de 01).
     */
    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public static function orderByCodigoAsc($query, string $column = 'codigo')
    {
        self::assertSafeColumnName($column);

        $driver = $query->getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            return $query->orderByRaw(
                "(CASE WHEN {$column} ~ '^[0-9]+$' THEN {$column}::bigint END) ASC NULLS LAST, {$column} ASC"
            );
        }

        return $query->orderByRaw(
            "(CASE WHEN {$column} REGEXP '^[0-9]+$' THEN CAST({$column} AS UNSIGNED) END) ASC, {$column} ASC"
        );
    }

    private static function assertSafeColumnName(string $column): void
    {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new \InvalidArgumentException("Nombre de columna inválido: {$column}");
        }
    }
}
