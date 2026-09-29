<?php

namespace App\Support;

use App\Models\Sold;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Predzakazli buyurtma: butun buyurtma eng kech chiqadigan kitob sanasigacha
 * kutadi (mijoz tanlovi — "hammasi birga keladi"). Shu kungacha kuryerga
 * chiqmaydi, nasiya va naqd yopiq.
 */
class OrderPreorder
{
    private static ?bool $hasColumn = null;

    public static function shipsAt(Sold $order): ?Carbon
    {
        self::$hasColumn ??= Schema::hasColumn('solds', 'preorder_ships_at');
        if (self::$hasColumn && $order->preorder_ships_at) {
            return Carbon::parse($order->preorder_ships_at)->startOfDay();
        }

        // Ustun bo'lmasa (yoki eski buyurtma) — qatorlardagi sanadan
        $max = null;
        foreach ((array) ($order->items ?? []) as $item) {
            $raw = is_array($item) ? ($item['preorder_release_date'] ?? null) : null;
            if (! $raw) {
                continue;
            }
            try {
                $date = Carbon::parse($raw)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
            if ($max === null || $date->gt($max)) {
                $max = $date;
            }
        }

        return $max;
    }

    /** Jo'natish kuni hali kelmagan — kuryerga chiqarilmaydi. */
    public static function isHeld(Sold $order): bool
    {
        $date = self::shipsAt($order);

        return $date !== null && $date->isAfter(today());
    }
}
