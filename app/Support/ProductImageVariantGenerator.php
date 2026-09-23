<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ProductImageVariantGenerator
{
    /**
     * XAVFSIZLIK: "dekompressiya bombasi" — hajmi kichik, lekin piksellari
     * juda katta rasm (masalan 25000x25000) GD'da bir necha GB xotira so'rab,
     * PHP jarayonini o'ldiradi. Shuning uchun o'lcham OLDIN o'qiladi.
     */
    private const MAX_PIXELS = 50_000_000; // ~50 MP (8000x6000 dan kattasi rad etiladi)

    public static function generateForPath(?string $path): int
    {
        $path = ltrim(trim((string) $path), '/');
        if ($path === '' || !Storage::disk('public')->exists($path) || !function_exists('imagecreatefromstring')) {
            return 0;
        }

        $absolutePath = Storage::disk('public')->path($path);

        $size = @getimagesize($absolutePath);
        if (is_array($size) && (int) $size[0] > 0 && (int) $size[1] > 0
            && (int) $size[0] * (int) $size[1] > self::MAX_PIXELS) {
            return 0;
        }

        $binary = @file_get_contents($absolutePath);
        if ($binary === false) {
            return 0;
        }

        $source = @imagecreatefromstring($binary);
        if (!$source) {
            return 0;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width < 1 || $height < 1) {
            imagedestroy($source);
            return 0;
        }

        $generated = 0;
        foreach (['thumb' => 480, 'medium' => 1200] as $variant => $targetWidth) {
            $variantPath = self::variantPath($path, $variant);
            if (Storage::disk('public')->exists($variantPath)) {
                continue;
            }

            $newWidth = min($targetWidth, $width);
            $newHeight = (int) round(($height / $width) * $newWidth);
            $canvas = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);

            imagecopyresampled(
                $canvas,
                $source,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $width,
                $height
            );

            $variantAbsolutePath = Storage::disk('public')->path($variantPath);
            $variantDir = dirname($variantAbsolutePath);
            if (!is_dir($variantDir)) {
                @mkdir($variantDir, 0775, true);
            }

            if (function_exists('imagewebp') && @imagewebp($canvas, $variantAbsolutePath, 82)) {
                $generated++;
            }

            imagedestroy($canvas);
        }

        imagedestroy($source);

        return $generated;
    }

    public static function deleteForPath(?string $path): void
    {
        $path = ltrim(trim((string) $path), '/');
        if ($path === '') {
            return;
        }

        foreach (['thumb', 'medium'] as $variant) {
            foreach (self::candidateVariantPaths($path, $variant) as $candidate) {
                if (Storage::disk('public')->exists($candidate)) {
                    Storage::disk('public')->delete($candidate);
                }
            }
        }
    }

    public static function variantPath(string $path, string $variant): string
    {
        $trimmed = ltrim($path, '/');
        $dir = pathinfo($trimmed, PATHINFO_DIRNAME);
        $filename = pathinfo($trimmed, PATHINFO_FILENAME);
        $dir = $dir === '.' ? '' : $dir;
        $prefix = $dir !== '' ? $dir . '/' : '';

        return "{$prefix}__variants/{$variant}_{$filename}.webp";
    }

    public static function candidateVariantPaths(string $path, string $variant): array
    {
        $trimmed = ltrim($path, '/');
        $dir = pathinfo($trimmed, PATHINFO_DIRNAME);
        $filename = pathinfo($trimmed, PATHINFO_FILENAME);
        $extension = pathinfo($trimmed, PATHINFO_EXTENSION);
        $dir = $dir === '.' ? '' : $dir;
        $prefix = $dir !== '' ? $dir . '/' : '';

        $candidates = [
            "{$prefix}__variants/{$variant}_{$filename}.webp",
            "{$prefix}variants/{$variant}_{$filename}.webp",
            "{$prefix}__variants/{$variant}_{$filename}.jpg",
            "{$prefix}__variants/{$variant}_{$filename}.jpeg",
            "{$prefix}__variants/{$variant}_{$filename}.png",
            "{$prefix}variants/{$variant}_{$filename}.jpg",
            "{$prefix}variants/{$variant}_{$filename}.jpeg",
            "{$prefix}variants/{$variant}_{$filename}.png",
        ];

        if ($extension !== '') {
            $candidates[] = "{$prefix}__variants/{$variant}_{$filename}.{$extension}";
            $candidates[] = "{$prefix}variants/{$variant}_{$filename}.{$extension}";
            $candidates[] = "{$prefix}{$variant}_{$filename}.{$extension}";
        }

        return array_values(array_unique($candidates));
    }
}
