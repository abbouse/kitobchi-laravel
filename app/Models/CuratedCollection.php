<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuratedCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'is_active',
        'sort_order',
        'title_uz',
        'title_ru',
        'title_en',
        'title_ja',
        'subtitle_uz',
        'subtitle_ru',
        'subtitle_en',
        'subtitle_ja',
        'description_uz',
        'description_ru',
        'description_en',
        'description_ja',
        'hero_image',
        'gradient_from',
        'gradient_to',
        'button_bg_color',
        'button_text_color',
        'custom_total_price',
        'delivery_price',
        'festive_effect',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'custom_total_price' => 'integer',
        'delivery_price' => 'integer',
        'festive_effect' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CuratedCollectionItem::class, 'collection_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** Faqat 1-daraja bo'limlar (parent_id null). Ichki bo'limlar children orqali olinadi. */
    public function sections(): HasMany
    {
        return $this->hasMany(CuratedCollectionSection::class, 'collection_id')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** Barcha bo'limlar (1 va 2-daraja) — sync/o'chirish uchun. */
    public function allSections(): HasMany
    {
        return $this->hasMany(CuratedCollectionSection::class, 'collection_id');
    }

    public function localized(string $field, string $locale = 'uz'): ?string
    {
        $preferred = $this->getAttribute("{$field}_{$locale}");
        if (filled($preferred)) {
            return $preferred;
        }

        foreach (['uz', 'ru', 'en', 'ja'] as $fallbackLocale) {
            $fallback = $this->getAttribute("{$field}_{$fallbackLocale}");
            if (filled($fallback)) {
                return $fallback;
            }
        }

        return null;
    }
}
