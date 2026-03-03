<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_id',
        'card',
        'amount',
        'commissionPercent',
        'commissionPrice',
        'netAmount',
        'status',
        'rejected_desc'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}