<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Sovg'alar g'ildiragi o'yini. */
class GamePrize extends Model
{
    protected $table = 'game_prizes';

    protected $guarded = ['id'];

    protected $casts = [
        'chance' => 'float',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'fragments_total' => 'integer',
        'coins_amount' => 'integer',
        'discount_value' => 'integer',
        'max_discount' => 'integer',
        'min_order_amount' => 'integer',
        'valid_days' => 'integer',
        'min_orders' => 'integer',
        'min_spins' => 'integer',
        'max_wins_per_user' => 'integer',
        'daily_limit' => 'integer',
        'stock' => 'integer',
        'won_count' => 'integer',
        'scope_id' => 'integer',
        'edition_id' => 'integer',
    ];

    /** Boshqaruvdagi tayyor foizlar (o'zgartirsa bo'ladi). */
    public const DIFFICULTY_PRESETS = ['easy' => 20.0, 'medium' => 7.0, 'hard' => 1.0];

    public function edition()
    {
        return $this->belongsTo(BookEdition::class, 'edition_id');
    }

    public function title(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return ($locale === 'ru' && $this->title_ru) ? $this->title_ru : $this->title_uz;
    }
}
