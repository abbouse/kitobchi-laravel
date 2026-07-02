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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CuratedCollectionItem::class, 'collection_id')
            ->orderBy('sort_order')
            ->orderBy('id');
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
