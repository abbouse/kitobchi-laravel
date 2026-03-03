<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sold extends Model
{
    use HasFactory;

    protected $table = 'solds';

    protected $fillable = [
        'deliveryType',
        'items',
        'user_id',
        'courier_id',
        'courierName',
        'amount',
        'status',
        'address',
        'qr',
        'gift',
        'buyerWish',
        'promocode',
        'discountAmount',
        'withCashback',
        'cashbackAmount',
        'deliveryPrice',
        'paymentStatus'
    ];

    protected $casts = [
        'address' => 'array',
        'items' => 'array',
        'withCashback' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function getGift(): BelongsTo
    {
        return $this->belongsTo(Gifts::class, 'gift', 'id');
    }
}
