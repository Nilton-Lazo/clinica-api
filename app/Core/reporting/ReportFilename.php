<?php

namespace App\Core\reporting;

use DateTimeInterface;

final class ReportFilename
{
    public static function buildStructured(
        string $modulo,
        string $tipoReporte,
        string $entidad,
        ?string $identificador,
        ReportFormat $format,
        ?DateTimeInterface $generatedAt = null,
    ): string {
        $at = $generatedAt ?? now();

        $parts = [
            self::segment($modulo),
            self::segment($tipoReporte),
            self::segment($entidad),
        ];

        $id = self::segmentIdentifier($identificador);
        if ($id !== '') {
            $parts[] = $id;
        }

        $parts[] = $at->format('Y-m-d');
        $parts[] = $at->format('H-i');

        return implode('_', $parts).'.'.$format->fileExtension();
    }

    public static function build(string $reportKey, ReportFormat $format, ?string $suffix = null): string
    {
        $segments = explode('.', strtolower($reportKey));
        $modulo = $segments[0] ?? 'reporte';
        $tipo = $segments[1] ?? 'export';
        $entidad = $segments[2] ?? 'general';

        return self::buildStructured($modulo, $tipo, $entidad, $suffix, $format);
    }

    private static function segment(string $value): string
    {
        $normalized = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';

        return trim($slug, '_');
    }

    private static function segmentIdentifier(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed === '—' || $trimmed === '-') {
            return '';
        }

        return self::segment($trimmed);
    }
}
