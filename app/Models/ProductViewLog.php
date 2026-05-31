<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductViewLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'product_id',
        'product_type',
        'user_id',
        'recommendation_active',
        'device_id',
        'session_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'recommendation_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
