<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ProductImageUrls
{
    public static function build($images): array
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            if (is_array($decoded)) {
                $images = $decoded;
            } elseif (trim($images) !== '') {
                $images = [$images];
            } else {
                $images = [];
            }
        }

        if (!is_array($images)) {
            $images = [];
        }

        $images = collect($images)
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

        $clean = self::normalizeStoragePath($path);
        if ($clean === '') {
            return null;
        }

        return asset('storage/' . $clean);
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

        $clean = self::normalizeStoragePath($path);
        foreach (self::candidateVariantPaths($clean, $variant) as $candidate) {
            if (Storage::disk('public')->exists($candidate)) {
                return asset('storage/' . ltrim($candidate, '/'));
            }
        }

        return self::originalUrl($clean);
    }

    public static function normalizeStoragePath(string $path): string
    {
        $path = trim($path);
        $path = str_replace('\\', '/', $path);
        if (str_starts_with($path, '/storage/')) {
            $path = substr($path, 9);
        } elseif (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        } elseif (str_starts_with($path, '/public/')) {
            $path = substr($path, 8);
        } elseif (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }
        return ltrim($path, '/');
    }

    private static function candidateVariantPaths(string $path, string $variant): array
    {
        return ProductImageVariantGenerator::candidateVariantPaths($path, $variant);
    }

    private static function isExternal(string $path): bool
    {
        return str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '//')
            || str_starts_with($path, 'data:');
    }
}
