<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'order_id',
        'client_id',
        'amount',
        'delivery_type',
        'address',
        'status',
    ];
    protected $casts = [
        'address' => 'json',
    ];

    public function items()
    {
        return $this->hasMany(SellerOrderItem::class, 'order_id', 'order_id');
    }
}
