<?php

namespace App\Models;

use App\Enums\SellerOrderStatusCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'order_id',
        'client_id',
        'courier_id',
        'courierName',
        'amount',
        'delivery_type',
        'address',
        'status',
        'status_code',
        'accepted_at',
    ];

    protected $casts = [
        'address' => 'array',
        'accepted_at' => 'datetime',
    ];

    public function getStatusCodeAttribute(?string $value): string
    {
        return $value ?: SellerOrderStatusCode::fromLegacy($this->attributes['status'] ?? null)->value;
    }

    // ── Relationships ──────────────────────────────────────────────

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id')
            ->select('id', 'name', 'lastname', 'avatar', 'phone_number');
    }

    // alias: user() — CourierOrder bilan mos bo'lsin
    public function user()
    {
        return $this->client();
    }

    public function courier()
    {
        return $this->belongsTo(Couriers::class, 'courier_id');
    }

    public function order()
    {
        return $this->belongsTo(Sold::class, 'order_id');
    }

    public function items()
    {
        return $this->hasMany(SellerOrderItem::class, 'order_id', 'id');
    }
}
