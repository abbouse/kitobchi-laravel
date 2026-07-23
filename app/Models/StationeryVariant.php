<?php

namespace App\Models;

use App\Services\BranchStockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StationeryVariant extends Model
{
    use HasFactory;

    protected $table = 'stationery_variants';

    /**
     * PERFORMANCE: variant `stock` accessor N+1 so'rovlarini kesish.
     * `with(['variants' => fn($q) => $q->withAvailableTotal()])` bilan ishlaydi.
     */
    public function scopeWithAvailableTotal(Builder $query): Builder
    {
        if (is_null($query->getQuery()->columns)) {
            $query->select('stationery_variants.*');
        }

        return $query->addSelect(DB::raw(
            "(SELECT COALESCE(SUM(bs.quantity - bs.reserved), 0)
              FROM branch_stocks bs
              WHERE bs.product_type = 'stationery'
                AND bs.product_id = stationery_variants.product_id
                AND bs.variant_id = stationery_variants.id) as branch_available_total"
        ));
    }

    protected $fillable = [
        'product_id',
        'color_name',
        'image_path',
    ];

    /** Legacy API kontrakt: variant `stock` JSON javoblarda saqlanadi. */
    protected $appends = ['stock'];

    /** Ichki subselect maydoni JSON'ga chiqmasin (API kontrakt o'zgarmas). */
    protected $hidden = ['branch_available_total'];

    public function getStockAttribute(): int
    {
        if (array_key_exists('branch_available_total', $this->attributes)) {
            return max(0, (int) $this->attributes['branch_available_total']);
        }

        return app(BranchStockService::class)
            ->totalAvailable('stationery', (int) $this->product_id, (int) $this->id);
    }

    public function setStockAttribute($value): void
    {
        Log::warning('StationeryVariant.stock setter ignored — use BranchStockService', [
            'variant_id' => $this->id, 'value' => $value,
        ]);
    }

    public function branchStocks()
    {
        return $this->hasMany(BranchStock::class, 'variant_id')
            ->where('product_type', 'stationery')
            ->where('product_id', $this->product_id ?? 0);
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Asosiy mahsulotga bog'lanish
    public function product()
    {
        return $this->belongsTo(Stationery::class, 'product_id');
    }

    // Rasm to'liq URL ni qaytarish (agar storage da saqlansa)
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        return null;
    }
}