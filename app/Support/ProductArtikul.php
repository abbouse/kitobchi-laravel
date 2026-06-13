<?php

namespace App\Support;

class ProductArtikul
{
    public static function generate(string $type, int $id): string
    {
        $prefix = match ($type) {
            'book' => '10',
            'stationery' => '20',
            'gift' => '30',
            default => '90',
        };

        return sprintf('%s%08d', $prefix, $id);
    }
}
