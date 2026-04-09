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
        'action',      // news | to_shop | to_product
        'action_id',   // seller.id yoki books.id
    ];

    protected $casts = [
        'status'    => 'boolean',
        'action_id' => 'integer',
    ];

    // Action turlari
    const ACTION_NEWS       = 'news';
    const ACTION_TO_SHOP    = 'to_shop';
    const ACTION_TO_PRODUCT = 'to_product';

    // Seller (to_shop)
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'action_id');
    }

    // Kitob (to_product)
    public function book()
    {
        return $this->belongsTo(Books::class, 'action_id');
    }

    // Action label
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            self::ACTION_TO_SHOP    => 'Do\'konga o\'tish',
            self::ACTION_TO_PRODUCT => 'Mahsulotga o\'tish',
            default                 => 'Yangilik',
        };
    }
}