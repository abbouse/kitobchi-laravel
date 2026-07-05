<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SplitCategoryRule extends Model
{
    protected $fillable = [
        'category_type',
        'category_id',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
