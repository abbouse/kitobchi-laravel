<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_id',
        'seller_location_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
        'type'
    ];

    public function product()
    {
        return $this->belongsTo(Books::class, 'product_id')
        ->select('id', 'name', 'author', 'seller_id', 'images');
    }
    public function orderStatus()
    {
        return $this->hasOne(SellerOrder::class, 'order_id', 'order_id')
            ->where('seller_id', $this->seller_id)
            ->select('status', 'seller_id', 'order_id');
    }

    public function sellerLocation()
    {
        return $this->hasOne(SellerLocation::class, 'id','seller_location_id');
    }
}
