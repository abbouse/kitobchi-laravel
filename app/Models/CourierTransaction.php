<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_id',
        'card',
        'type',
        'category',
        'order_id',
        'courier_order_id',
        'courier_task_id',
        'amount',
        'commissionPercent',
        'commissionPrice',
        'netAmount',
        'status',
        'description',
        'rejected_desc'
    ];

    protected $casts = [
        'amount' => 'integer',
        'commissionPercent' => 'integer',
        'commissionPrice' => 'integer',
        'netAmount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Couriers::class, 'courier_id');
    }
}
