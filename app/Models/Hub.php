<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hub extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'country_code',
        'region_name',
        'city_name',
        'address',
        'lat',
        'lon',
        'is_active',
        'is_primary',
        'priority',
        'supports_first_mile',
        'supports_last_mile',
        'supports_postal_dispatch',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'priority' => 'integer',
        'supports_first_mile' => 'boolean',
        'supports_last_mile' => 'boolean',
        'supports_postal_dispatch' => 'boolean',
        'meta' => 'array',
        'lat' => 'float',
        'lon' => 'float',
    ];

    public function staff()
    {
        return $this->hasMany(HubStaff::class);
    }

    public function fulfillments()
    {
        return $this->hasMany(OrderFulfillment::class);
    }

    public function courierTasks()
    {
        return $this->hasMany(CourierTask::class);
    }
}
