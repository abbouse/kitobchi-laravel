<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashbackSetting extends Model
{
    public const TYPE_DELIVERY = 'delivery';
    public const TYPE_PICKUP   = 'pickup';

    protected $fillable = ['fromUzs', 'toUzs', 'cashback', 'type'];

    /**
     * Buyurtma turiga qarab ($type) tegishli tier'dan cashback foizini
     * qaytaradi. Eski chaqiruvchilar uchun default 'delivery' qoldiriladi.
     *
     * Pickup tier (do'kon ichida xarid) topilmasa, fallback sifatida
     * delivery tieridan foydalanamiz — shunday qilib, admin pickup
     * tarif yaratmagan bo'lsa ham mijoz cashback olishda davom etadi.
     */
    public static function getCashbackPercentage(
        int $amountInUzs,
        string $type = self::TYPE_DELIVERY
    ): int {
        $row = self::where('type', $type)
            ->where('fromUzs', '<=', $amountInUzs)
            ->where('toUzs', '>=', $amountInUzs)
            ->first();

        if (!$row && $type === self::TYPE_PICKUP) {
            // Fallback — pickup qoidasi yo'q bo'lsa delivery'ni ishlatamiz.
            $row = self::where('type', self::TYPE_DELIVERY)
                ->where('fromUzs', '<=', $amountInUzs)
                ->where('toUzs', '>=', $amountInUzs)
                ->first();
        }

        return $row ? (int) $row->cashback : 0;
    }
}