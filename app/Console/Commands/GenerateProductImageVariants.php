<?php

namespace App\Console\Commands;

use App\Models\Books;
use App\Models\Gifts;
use App\Models\Stationery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateProductImageVariants extends Command
{
    protected $signature = 'images:generate-variants {--type=all : books|stationery|gifts|all} {--limit=0 : Max model rows to process}';
    protected $description = 'Generate thumb and medium variants for stored product images.';

    public function handle(): int
    {
        if (!function_exists('imagecreatefromstring')) {
            $this->error('GD extension is not available.');
            return self::FAILURE;
        }

        $type = strtolower((string) $this->option('type'));
        $limit = max(0, (int) $this->option('limit'));

        $targets = match ($type) {
            'books', 'book' => [['label' => 'books', 'model' => Books::class]],
            'stationery' => [['label' => 'stationery', 'model' => Stationery::class]],
            'gifts', 'gift' => [['label' => 'gifts', 'model' => Gifts::class]],
            default => [
                ['label' => 'books', 'model' => Books::class],
                ['label' => 'stationery', 'model' => Stationery::class],
                ['label' => 'gifts', 'model' => Gifts::class],
            ],
        };

        foreach ($targets as $target) {
            $this->processModel($target['label'], $target['model'], $limit);
        }

        return self::SUCCESS;
    }

    private function processModel(string $label, string $modelClass, int $limit): void
    {
        $this->info("Processing {$label}...");

        $query = $modelClass::query()->select(['id', 'images']);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $processed = 0;
        $generated = 0;

        $query->chunkById(100, function ($rows) use (&$processed, &$generated) {
            foreach ($rows as $row) {
                $images = is_array($row->images) ? $row->images : [];
                foreach ($images as $image) {
                    if (!is_string($image) || trim($image) === '') {
                        continue;
                    }

                    $processed++;
                    $generated += $this->generateForPath($image);
                }
            }
        });

        $this->line("{$label}: checked {$processed} images, generated {$generated} variants.");
    }

    private function generateForPath(string $path): int
    {
        $path = ltrim($path, '/');
        if (!Storage::disk('public')->exists($path)) {
            return 0;
        }

        $absolutePath = Storage::disk('public')->path($path);
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
            $variantPath = $this->variantPath($path, $variant);
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

            if (function_exists('imagewebp')) {
                @imagewebp($canvas, $variantAbsolutePath, 82);
            }

            imagedestroy($canvas);
            $generated++;
        }

        imagedestroy($source);

        return $generated;
    }

    private function variantPath(string $path, string $variant): string
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $dir = $dir === '.' ? '' : $dir;
        $prefix = $dir !== '' ? $dir . '/' : '';

        return "{$prefix}variants/{$variant}_{$filename}.webp";
    }
}
