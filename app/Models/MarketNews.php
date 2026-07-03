<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketNews extends Model
{
    use HasFactory;

    protected $table = 'market_news';

    protected $fillable = [
        'imgUrl',
        'title',
        'title_uz',
        'title_ru',
        'title_en',
        'title_ja',
        'description',
        'description_uz',
        'description_ru',
        'description_en',
        'description_ja',
        'align',
        'status',
        'action',
        'action_id',
    ];

    protected $casts = [
        'status'    => 'boolean',
        'action_id' => 'integer',
    ];

    public const ACTION_NEWS = 'news';
    public const ACTION_TO_BOTTOMSHEET = 'to_bottomsheet';
    public const ACTION_TO_SHOP = 'to_shop';
    public const ACTION_TO_PRODUCT = 'to_product';
    public const ACTION_TO_COLLECTION = 'to_collection';

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'action_id');
    }

    public function book()
    {
        return $this->belongsTo(Books::class, 'action_id');
    }

    public function collection()
    {
        return $this->belongsTo(CuratedCollection::class, 'action_id');
    }

    public static function allowedActions(): array
    {
        return [
            self::ACTION_NEWS,
            self::ACTION_TO_BOTTOMSHEET,
            self::ACTION_TO_SHOP,
            self::ACTION_TO_PRODUCT,
            self::ACTION_TO_COLLECTION,
        ];
    }

    public function normalizedAction(): string
    {
        return match ($this->action) {
            'to_book' => self::ACTION_TO_PRODUCT,
            'to_catalog' => self::ACTION_TO_BOTTOMSHEET,
            self::ACTION_NEWS => self::ACTION_TO_BOTTOMSHEET,
            default => $this->action ?: self::ACTION_TO_BOTTOMSHEET,
        };
    }

    public function getActionLabelAttribute(): string
    {
        return match($this->normalizedAction()) {
            self::ACTION_TO_SHOP    => 'Do\'konga o\'tish',
            self::ACTION_TO_PRODUCT => 'Mahsulotga o\'tish',
            self::ACTION_TO_COLLECTION => 'To‘plamga o‘tish',
            default => 'Bottomsheet',
        };
    }

    public function localized(string $field, string $locale = 'uz'): ?string
    {
        $preferred = $this->getAttribute("{$field}_{$locale}");
        if (filled($preferred)) {
            return $preferred;
        }

        if ($field === 'title' || $field === 'description') {
            $legacy = $this->getAttribute($field);
            if (filled($legacy)) {
                return $legacy;
            }
        }

        foreach (['uz', 'ru', 'en', 'ja'] as $fallbackLocale) {
            $fallback = $this->getAttribute("{$field}_{$fallbackLocale}");
            if (filled($fallback)) {
                return $fallback;
            }
        }

        return $field === 'title' || $field === 'description'
            ? $this->getAttribute($field)
            : null;
    }
}
