<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuratedCollectionSection extends Model
{
    protected $fillable = [
        'collection_id',
        'parent_id',
        'name_uz',
        'name_ru',
        'name_en',
        'name_ja',
        'custom_total_price',
        'sort_order',
    ];

    protected $casts = [
        'custom_total_price' => 'integer',
        'sort_order' => 'integer',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(CuratedCollection::class, 'collection_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CuratedCollectionItem::class, 'section_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function localizedName(string $locale = 'uz'): ?string
    {
        $preferred = $this->getAttribute("name_{$locale}");
        if (filled($preferred)) {
            return $preferred;
        }

        foreach (['uz', 'ru', 'en', 'ja'] as $fallback) {
            $value = $this->getAttribute("name_{$fallback}");
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }
}
