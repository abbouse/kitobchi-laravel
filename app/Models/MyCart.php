<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyCart extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'product_id',
        'product_type',     // 'book', 'stationery'
        'variant_id',
        'count_item',
        'priceItem',
    ];

    /**
     * Polymorphic relation — umumiy mahsulot (book yoki stationery)
     */
    public function product()
    {
        return $this->morphTo('product', 'product_type', 'product_id');
    }

    /**
     * Variant (faqat stationery bo‘lsa)
     */
    public function variant()
    {
        return $this->belongsTo(StationeryVariant::class, 'variant_id');
    }

    // Accessorlar (helperlar)
    public function getProductNameAttribute()
    {
        return $this->product?->name ?? 'Noma\'lum';
    }

    public function getProductImageAttribute()
    {
        $images = $this->product?->images ?? [];
        return is_array($images) && count($images) > 0 ? $images[0] : null;
    }

    public function getProductPriceAttribute()
{
    // Agar variant tanlangan bo'lsa (stationery uchun)
    if ($this->product_type === 'stationery' && $this->variant_id) {
        $variant = $this->variant;
        if ($variant) {
            // Variantning o'z narxi bo'lsa shuni, bo'lmasa asosiy mahsulot narxi
            return $variant->price > 0 ? $variant->price : ($this->product->discount_price > 0 ? $this->product->discount_price : $this->product->price);
        }
    }

    $product = $this->product;
    if (!$product) return 0;

    if ($this->product_type === 'book') {
        return $product->discountPrice > 0 ? $product->discountPrice : $product->price;
    }

    return $product->discount_price > 0 ? $product->discount_price : $product->price;
}

public function getProductStockAttribute()
{
    // Agar variant bo'lsa, variantning stock'ini qaytaramiz
    if ($this->product_type === 'stationery' && $this->variant_id) {
        return $this->variant?->stock ?? 0;
    }

    $product = $this->product;
    if (!$product) return 0;

    return $this->product_type === 'book' ? $product->count : $product->stock;
}
}