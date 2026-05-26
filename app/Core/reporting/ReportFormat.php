<?php

namespace App\Core\reporting;

enum ReportFormat: string
{
    case Pdf = 'pdf';
    case Xlsx = 'xlsx';
    case Csv = 'csv';

    public static function tryFromRequest(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom(strtolower(trim($value)));
    }

    public static function valuesList(): array
    {
        return array_map(fn (self $f) => $f->value, self::cases());
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Pdf => 'application/pdf',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Csv => 'text/csv; charset=UTF-8',
        };
    }

    public function fileExtension(): string
    {
        return $this->value;
    }
}
