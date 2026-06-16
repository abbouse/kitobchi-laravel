<?php

namespace App\Models;

use App\Enums\CourierOrderStatusCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_id',
        'order_id',
        'user_id',
        'status',
        'status_code',
        'amount',
        'courierPrice',
        'courierBonus',
        'settled_amount',
        'settled_at',
        'picked_up_at',
    ];

    protected $casts = [
        'picked_up_at'              => 'datetime',
        'settled_amount'            => 'integer',
        'settled_at'                => 'datetime',
    ];

    public function getStatusCodeAttribute(?string $value): string
    {
        return $value ?: CourierOrderStatusCode::fromLegacy($this->attributes['status'] ?? null)->value;
    }

    // ── Relationships ──────────────────────────────────────────────

    public function courier()
    {
        return $this->belongsTo(Couriers::class, 'courier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')
            ->select('id', 'name', 'lastname', 'avatar', 'phone_number', 'mainAddressID');
    }

    // alias: customer() — eski kod uchun
    public function customer()
    {
        return $this->user();
    }

    public function order()
    {
        return $this->belongsTo(Sold::class, 'order_id');
    }

    public function items()
    {
        return $this->hasMany(CourierOrderItem::class, 'order_id', 'order_id');
    }

    public function paymentStatus()
    {
        return $this->belongsTo(Sold::class, 'order_id')
            ->select('id', 'paymentStatus');
    }
}
