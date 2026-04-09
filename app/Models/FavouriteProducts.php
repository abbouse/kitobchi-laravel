<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavouriteProducts extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'product_type',
        'variant_id',   // ← yangi ustun (stationery varianti uchun)
    ];

    /**
     * Polymorphic relation — umumiy mahsulot (book yoki stationery)
     */
    public function product()
    {
        return $this->morphTo('product', 'product_type', 'product_id');
    }

    /**
     * Tanlangan stationery varianti
     */
    public function variant()
    {
        return $this->belongsTo(StationeryVariant::class, 'variant_id');
    }
}