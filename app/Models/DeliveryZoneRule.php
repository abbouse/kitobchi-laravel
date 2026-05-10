<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZoneRule extends Model
{
    protected $fillable = [
        'zone_name',
        'country_code',
        'scope',
        'region_name',
        'district_name',
        'city_name',
        'center_lat',
        'center_lon',
        'radius_km',
        'delivery_service_id',
        'priority',
        'base_price',
        'additional_seller_percent',
        'free_price_from',
        'eta_days',
        'cod_allowed',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'center_lat' => 'float',
        'center_lon' => 'float',
        'radius_km' => 'float',
        'priority' => 'integer',
        'base_price' => 'integer',
        'additional_seller_percent' => 'float',
        'free_price_from' => 'integer',
        'eta_days' => 'integer',
        'cod_allowed' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function deliveryService(): BelongsTo
    {
        return $this->belongsTo(DeliveryService::class, 'delivery_service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
