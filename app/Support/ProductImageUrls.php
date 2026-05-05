<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ProductImageUrls
{
    public static function build(?array $images): array
    {
        $images = collect($images ?? [])
            ->filter(fn ($image) => is_string($image) && trim($image) !== '')
            ->values();

        return [
            'original' => $images->map(fn (string $image) => self::originalUrl($image))->filter()->values()->all(),
            'medium' => $images->map(fn (string $image) => self::variantUrl($image, 'medium'))->filter()->values()->all(),
            'thumb' => $images->map(fn (string $image) => self::variantUrl($image, 'thumb'))->filter()->values()->all(),
        ];
    }

    public static function originalUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (self::isExternal($path)) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    public static function variantUrl(?string $path, string $variant): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (self::isExternal($path)) {
            return $path;
        }

        foreach (self::candidateVariantPaths($path, $variant) as $candidate) {
            if (Storage::disk('public')->exists($candidate)) {
                return asset('storage/' . ltrim($candidate, '/'));
            }
        }

        return self::originalUrl($path);
    }

    private static function candidateVariantPaths(string $path, string $variant): array
    {
        $trimmed = ltrim($path, '/');
        $dir = pathinfo($trimmed, PATHINFO_DIRNAME);
        $filename = pathinfo($trimmed, PATHINFO_FILENAME);
        $extension = pathinfo($trimmed, PATHINFO_EXTENSION);

        $dir = $dir === '.' ? '' : $dir;
        $prefix = $dir !== '' ? $dir . '/' : '';

        $candidates = [
            "{$prefix}variants/{$variant}_{$filename}.webp",
            "{$prefix}variants/{$variant}_{$filename}.jpg",
            "{$prefix}variants/{$variant}_{$filename}.jpeg",
            "{$prefix}variants/{$variant}_{$filename}.png",
        ];

        if ($extension !== '') {
            $candidates[] = "{$prefix}variants/{$variant}_{$filename}.{$extension}";
            $candidates[] = "{$prefix}{$variant}_{$filename}.{$extension}";
        }

        return array_values(array_unique($candidates));
    }

    private static function isExternal(string $path): bool
    {
        return str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, 'data:')
            || str_starts_with($path, '/storage/')
            || str_starts_with($path, '/');
    }
}
