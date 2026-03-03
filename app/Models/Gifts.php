<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gifts extends Model
{
    use HasFactory;
    protected $table = 'gifts';
    protected $fillable = [
        'seller_id',
        'name',
        'images',
        'stock',
        'priceFrom',
        'priceTo',
        'is_approved',
        'status',
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
    ];
    
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id')
        ->select('id', 'shop_name', 'photo');
    }
}
