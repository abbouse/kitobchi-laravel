<?php

namespace App\Console\Commands;

use App\Models\Books;
use App\Models\Gifts;
use App\Models\Stationery;
use App\Support\ProductImageVariantGenerator;
use Illuminate\Console\Command;

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
                    $generated += ProductImageVariantGenerator::generateForPath($image);
                }
            }
        });

        $this->line("{$label}: checked {$processed} images, generated {$generated} variants.");
    }
}
