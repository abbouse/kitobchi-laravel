<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stationery extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'material',
        'price',
        'discount_price',
        'stock',
        'description',
        'is_hidden',
        'is_approved',
        'status',
        'images',
        'totalSales',
        'totalClients',
        'totalRevenue',
        'totalSalesWeek',
        'totalClientsWeek',
        'totalRevenueWeek',
        'vectorData'
    ];

    protected $casts = [
        'images' => 'array',
        'is_hidden' => 'boolean',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Category bilan bog'lanish
    public function category()
    {
        return $this->belongsTo(StationeryCategory::class, 'category_id');
    }
    
    public function tags()
{
    return $this->belongsToMany(StationeryTag::class, 'stationery_tag_relations', 'stationery_id', 'tag_id');
}

    // Rang variantlari
    public function variants()
    {
        return $this->hasMany(StationeryVariant::class, 'product_id');
    }
    
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id')
        ->select('id', 'shop_name', 'lastname', 'firstname', 'phone_number', 'photo', 'isVerified');
    }

    // Helper: chegirma foizini hisoblash
    public function getDiscountPercentAttribute()
    {
        if ($this->discount_price > 0 && $this->price > $this->discount_price) {
            return round((($this->price - $this->discount_price) / $this->price) * 100);
        }
        return 0;
    }
}