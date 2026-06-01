<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Stationery extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'barcode',
        'material',
        'price',
        'discount_price',
        'discountExpiresAt',
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
        'vectorData',
        'recommended',
        'views',
        'recommendedExpiresAt',
        'kangaroo_listing_decision',
        'kangaroo_listing_score',
        'kangaroo_listing_checked_at',
        'kangaroo_listing_issues',
        'ugc_aggregate_score',
        'ugc_reviews_count',
        'ugc_last_scored_at',
    ];

    protected $casts = [
        'images' => 'array',
        'is_hidden' => 'boolean',
        'status' => 'boolean',
        'recommended' => 'boolean',
        'vectorData' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'kangaroo_listing_issues' => 'array',
        'kangaroo_listing_score' => 'decimal:1',
        'kangaroo_listing_checked_at' => 'datetime',
        'ugc_last_scored_at' => 'datetime',
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
            ->select('id', 'shop_name', 'lastname', 'firstname', 'phone_number', 'photo', 'isVerified', 'status', 'is_hidden');
    }

    public function scopeActiveForVector(Builder $query): Builder
    {
        return $query
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->where('stock', '>', 0)
            ->whereHas('seller', fn (Builder $sellerQuery) => $sellerQuery
                ->where('status', 'approved')
                ->where('is_hidden', 0));
    }

    public function scopeVectorReady(Builder $query): Builder
    {
        return $query
            ->whereNotNull('vectorData')
            ->whereRaw('JSON_LENGTH(vectorData) = 1536');
    }

    public function scopeVectorNeedsSync(Builder $query): Builder
    {
        return $query->where(function (Builder $innerQuery) {
            $innerQuery
                ->whereNull('vectorData')
                ->orWhereRaw('JSON_LENGTH(vectorData) <> 1536');
        });
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
