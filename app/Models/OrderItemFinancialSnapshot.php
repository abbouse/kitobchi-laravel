<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemFinancialSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'sold_id',
        'seller_order_id',
        'seller_order_item_id',
        'seller_id',
        'product_id',
        'variant_id',
        'product_type',
        'quantity',
        'gross_amount',
        'promo_allocated',
        'cashback_allocated',
        'gift_cert_allocated',
        'card_paid_allocated',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
