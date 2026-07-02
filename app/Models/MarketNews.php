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
        'description',
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
    public const ACTION_TO_CATALOG = 'to_catalog';

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
            self::ACTION_TO_CATALOG,
        ];
    }

    public function normalizedAction(): string
    {
        return match ($this->action) {
            'to_book' => self::ACTION_TO_PRODUCT,
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
            self::ACTION_TO_CATALOG => 'To‘plamlar katalogi',
            default => 'Bottomsheet',
        };
    }
}
