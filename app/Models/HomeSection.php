<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class HomeSection extends Model
{
    public const CACHE_KEY = 'home_sections:v1';

    /** Boshqaruvda qo'shiladigan (takrorlanadigan) turlar */
    public const CUSTOM_TYPES = ['category', 'collection'];

    public const TYPES = [
        'genres' => 'Janr chiplari',
        'recently_viewed' => "Oxirgi ko'rilganlar (shaxsiy)",
        'for_you' => 'Siz uchun (shaxsiy tavsiya)',
        'bestsellers' => "Haftaning ko'p sotilgani",
        'center_banners' => "O'rta bannerlar",
        'new_arrivals' => 'Yangi kitoblar',
        'coming_soon' => 'Tez orada (predzakaz)',
        'discount_ending' => 'Chegirma tugayapti',
        'collections' => "To'plamlar qatori",
        'club_trending' => 'Klubda muhokama qilinayotgan',
        'category' => 'Tanlangan janr kitoblari',
        'collection' => 'Tanlangan to\'plam kitoblari',
        'shops' => "Do'konlar (pastda, scroll bilan yuklanadi)",
    ];

    protected $fillable = [
        'key', 'type', 'title_uz', 'title_ru', 'title_en', 'title_ja',
        'is_active', 'position', 'item_limit', 'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'position' => 'integer',
        'item_limit' => 'integer',
    ];

    protected static function booted(): void
    {
        $forget = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($forget);
        static::deleted($forget);
    }

    public function title(string $locale): string
    {
        $value = match ($locale) {
            'ru' => $this->title_ru,
            'en' => $this->title_en,
            'ja' => $this->title_ja,
            default => $this->title_uz,
        };

        return (string) ($value ?: $this->title_uz ?: (self::TYPES[$this->type] ?? $this->key));
    }

    /** Faol bo'limlar tartib bilan (1 daqiqa kesh). */
    public static function activeOrdered()
    {
        return Cache::remember(self::CACHE_KEY, 60, fn () => self::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get());
    }
}
