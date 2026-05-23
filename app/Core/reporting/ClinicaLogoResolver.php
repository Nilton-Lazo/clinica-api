<?php

namespace App\Core\reporting;

use Illuminate\Support\Facades\Storage;

final class ClinicaLogoResolver
{
    public static function resolve(?string $logoPath): array
    {
        $raw = trim((string) $logoPath);
        if ($raw === '') {
            return [null, null];
        }

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return [$raw, null];
        }

        $relative = ltrim(str_replace('\\', '/', $raw), '/');

        if (Storage::disk('public')->exists($relative)) {
            $absolute = Storage::disk('public')->path($relative);
            $url = Storage::disk('public')->url($relative);

            return [$url, self::dompdfImagePath($absolute, $relative)];
        }

        $legacyPublic = public_path($relative);
        if (is_file($legacyPublic)) {
            return [asset($relative), self::dompdfImagePath($legacyPublic, $relative)];
        }

        return [null, null];
    }

    private static function dompdfImagePath(string $absolutePath, string $relative): string
    {
        $viaSymlink = public_path('storage/'.ltrim($relative, '/'));
        if (is_file($viaSymlink)) {
            return str_replace('\\', '/', realpath($viaSymlink) ?: $viaSymlink);
        }

        return str_replace('\\', '/', realpath($absolutePath) ?: $absolutePath);
    }
}
