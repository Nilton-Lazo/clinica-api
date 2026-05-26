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

    public static function dataUri(?string $logoPath): ?string
    {
        $absolute = self::resolveReadablePath($logoPath);
        if ($absolute === null || ! is_readable($absolute)) {
            return null;
        }

        $mime = mime_content_type($absolute);
        if (! is_string($mime) || ! str_starts_with($mime, 'image/')) {
            $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => null,
            };
        }

        if ($mime === null) {
            return null;
        }

        $contents = file_get_contents($absolute);
        if ($contents === false || $contents === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private static function resolveReadablePath(?string $logoPath): ?string
    {
        $raw = trim((string) $logoPath);
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', $raw), '/');

        if (Storage::disk('public')->exists($relative)) {
            return Storage::disk('public')->path($relative);
        }

        $viaSymlink = public_path('storage/'.ltrim($relative, '/'));
        if (is_file($viaSymlink)) {
            return realpath($viaSymlink) ?: $viaSymlink;
        }

        $legacyPublic = public_path($relative);
        if (is_file($legacyPublic)) {
            return realpath($legacyPublic) ?: $legacyPublic;
        }

        return null;
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
