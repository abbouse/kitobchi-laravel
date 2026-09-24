<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Katalog joyi narxi — boshqaruvdan boshqariladi. Jadvalda doim bitta qator.
 */
class CatalogSlotSetting extends Model
{
    protected $fillable = ['price_per_month', 'min_days', 'max_days', 'is_active'];

    protected $casts = [
        'price_per_month' => 'integer',
        'min_days' => 'integer',
        'max_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->orderBy('id')->first()
            ?? static::query()->create([
                'price_per_month' => 0,
                'min_days' => 7,
                'max_days' => 90,
                'is_active' => false,
            ]);
    }
}
