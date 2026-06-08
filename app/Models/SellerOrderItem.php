<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerOrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'seller_id', 'order_id', 'product_id', 'variant_id', 'type', 'quantity', 'price',
        'cancelled_at', 'cancelled_by_seller_id', 'cancel_reason_code',
        'cancel_note_uz', 'cancel_note_ru', 'cancel_note_en', 'cancel_note_ja',
        'custom_cancel_note', 'refund_status', 'refunded_at',
    ];
    protected $casts = [
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];
    public function book()
    {
        return $this->belongsTo(Books::class, 'product_id');
    }

    public function stationery()
    {
        return $this->belongsTo(Stationery::class, 'product_id');
    }
    
    public function variant()
{
    return $this->belongsTo(StationeryVariant::class, 'variant_id');
}

    public function gift()
    {
        return $this->belongsTo(Gifts::class, 'product_id');
    }

    /**
     * 🔥 TYPE ga qarab to‘g‘ri product qaytaradi
     */
    public function getProductAttribute()
    {
        $product = match ($this->type) {
        'book' => $this->book,
        'stationery' => $this->stationery,
        'gift' => $this->gift,
        default => null,
    };
        return [
        'parent' => $product,
        'variant' => $this->variant
    ];
    }
}
