<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTemplate extends Model
{
    protected $fillable = [
        'title', 'body', 'audience', 'category', 'shortcut', 'sort', 'is_active', 'usage_count', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
        'usage_count' => 'integer',
    ];
}
