<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerAiAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'seller_id',
        'requested_by_seller_id',
        'action_type',
        'status',
        'source_file_name',
        'summary',
        'payload',
        'result',
        'applied_at',
        'rolled_back_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'applied_at' => 'datetime',
        'rolled_back_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'requested_by_seller_id');
    }
}
