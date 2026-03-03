<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerAd extends Model
{
    protected $fillable = [
        'seller_id',
        'type',
        'description',
        'action',
        'product_id',
        'product_type',
        'banner_img',
        'days',
        'expire_at',
        'moderation',
        'paymentStatus',
        'amount'
    ];

    protected $casts = [
        'expire_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
    public function product()
    {
        return $this->belongsTo(Books::class, 'product_id');
    }
}