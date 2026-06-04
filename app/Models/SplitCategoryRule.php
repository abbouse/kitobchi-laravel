<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SplitCategoryRule extends Model
{
    protected $fillable = [
        'category_type',
        'category_id',
        'enabled',
        'fee_percent',
        'min_order_sum_override',
        'max_order_sum_override',
        'upfront_percent_override',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'fee_percent' => 'decimal:2',
    ];
}
