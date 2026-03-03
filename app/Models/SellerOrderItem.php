<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerOrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'seller_id', 'order_id', 'product_id', 'variant_id', 'type', 'quantity', 'price'
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
