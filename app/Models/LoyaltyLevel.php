<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Kitobxon darajasi (xarid bo'yicha). Boshqaruvda tahrirlanadi. */
class LoyaltyLevel extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['min_orders' => 'integer', 'min_spent' => 'integer', 'sort' => 'integer'];

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('loyalty_levels:all');
        static::saved($flush);
        static::deleted($flush);
    }

    /** @return \Illuminate\Support\Collection<int, self> pastdan yuqoriga */
    public static function ordered()
    {
        return Cache::remember('loyalty_levels:all', 3600, fn () => self::query()->orderBy('sort')->orderBy('id')->get());
    }

    public function name(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $v = $this->{'name_'.$locale} ?? null;

        return $v ?: $this->name_uz;
    }

    /** @return array<int, string> */
    public function perks(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $raw = $this->{'perks_'.$locale} ?: $this->perks_uz;

        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $raw) ?: [])));
    }
}
