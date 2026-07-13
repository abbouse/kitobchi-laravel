<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stationery extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'artikul',
        'barcode',
        'material',
        'price',
        'discount_price',
        'discountExpiresAt',
        'stock',
        'description',
        'is_hidden',
        'is_approved',
        'ai_moderation_status',
        'ai_moderation_checked_at',
        'ai_moderation_note',
        'ai_moderation_model',
        'ai_moderation_content_hash',
        'ai_moderation_attempts',
        'ai_moderation_next_retry_at',
        'ai_moderation_meta',
        'status',
        'images',
        'totalSales',
        'totalClients',
        'totalRevenue',
        'totalSalesWeek',
        'totalClientsWeek',
        'totalRevenueWeek',
        'vectorData',
        'vector_text_hash',
        'ofd_ikpu_code',
        'ofd_package_code',
        'recommended',
        'views',
        'recommendedExpiresAt',
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
        'ai_moderation_checked_at' => 'datetime',
        'ai_moderation_next_retry_at' => 'datetime',
        'ai_moderation_meta' => 'array',
        'ai_moderation_attempts' => 'integer',
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
        // Eslatma: stock sharti YO'Q — tugagan mahsulotlar ham chatbotda
        // ko'rinadi (stock-alert uchun). Asosiy search in-stock filterlaydi.
        return $query
            ->where('status', true)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
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
                ->orWhereRaw('JSON_LENGTH(vectorData) <> 1536')
                // Bulk yangilanishlar hash ni null qiladi — scheduler qayta embed qiladi
                ->orWhereNull('vector_text_hash');
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
