<?php

namespace App\Models;

use App\Models\Concerns\HasBranchStock;
use App\Services\BranchStockService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;

class Gifts extends Model
{
    use HasBranchStock, HasFactory;
    protected $table = 'gifts';

    /** Legacy API kontrakt: `stock` JSON javoblarda saqlanadi. */
    protected $appends = ['stock'];

    /** Ichki subselect maydoni JSON'ga chiqmasin (API kontrakt o'zgarmas). */
    protected $hidden = ['branch_available_total'];

    public function branchStockType(): string
    {
        return 'gift';
    }

    public function getStockAttribute(): int
    {
        if (array_key_exists('branch_available_total', $this->attributes)) {
            return max(0, (int) $this->attributes['branch_available_total']);
        }

        return app(BranchStockService::class)->totalAvailable('gift', (int) $this->id, 0);
    }

    public function setStockAttribute($value): void
    {
        Log::warning('Gifts.stock setter ignored — use BranchStockService', [
            'gift_id' => $this->id, 'value' => $value,
        ]);
    }

    protected $fillable = [
        'seller_id',
        'artikul',
        'name',
        'images',
        'priceFrom',
        'priceTo',
        'is_approved',
        'status',
        'archived_at',
        'totalSales',
        'totalClients',
        'totalRevenue',
        'totalSalesWeek',
        'totalClientsWeek',
        'totalRevenueWeek',
    ];
    protected $casts = [
        'images' => 'array',
        'status' => 'boolean',
        'archived_at' => 'datetime',
    ];
    
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id')
        ->select('id', 'shop_name', 'photo');
    }
}
