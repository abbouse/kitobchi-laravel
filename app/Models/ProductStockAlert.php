<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStockAlert extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'product_type',
        'variant_id',
        'notified_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];
}
