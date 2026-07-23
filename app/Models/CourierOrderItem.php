<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierOrderItem extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (CourierOrderItem $item): void {
            if ($item->type === 'gift' && (int) $item->seller_id === 1) {
                throw new \LogicException(
                    "Kitobchi platforma sovg'asi kuryer order itemiga qo'shilmaydi."
                );
            }
        });
    }

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
        // withAvailableTotal — `count` accessori N+1 qilmasin (stock subselect bilan keladi)
        return $this->belongsTo(Books::class, 'product_id')
        ->select('id', 'name', 'author', 'seller_id', 'images')
        ->withAvailableTotal();
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
