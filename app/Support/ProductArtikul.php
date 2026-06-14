<?php

namespace App\Support;

class ProductArtikul
{
    private const SEQUENCE_WIDTH = 6;
    private const SEQUENCE_CAPACITY = 999999;

    public static function generate(string $type, int $id): string
    {
        $basePrefix = self::basePrefix($type);
        $safeId = max(1, $id);
        $bucket = intdiv($safeId - 1, self::SEQUENCE_CAPACITY);
        $prefix = $basePrefix + $bucket;
        $sequence = (($safeId - 1) % self::SEQUENCE_CAPACITY) + 1;

        if ($prefix > 99) {
            throw new \OverflowException("Artikul prefix range is full for product type [{$type}].");
        }

        return sprintf('%02d%0' . self::SEQUENCE_WIDTH . 'd', $prefix, $sequence);
    }

    public static function basePrefix(string $type): int
    {
        return match ($type) {
            'book' => 10,
            'stationery' => 20,
            'gift' => 30,
            default => 90,
        };
    }
}
