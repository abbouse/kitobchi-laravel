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
        // Phase 3 — bonus tizimi
        'pickup_bonus',
        'locked_bonus',
        'final_bonus',
        'picked_up_at',
        'sla_deadline',
        'is_customer_delay',
        'customer_delay_started_at',
        'customer_delay_count',
        'total_delay_seconds',
        'bonus_threshold_notified',
        'sla_warning_notified',
    ];

    protected $casts = [
        'is_customer_delay'         => 'boolean',
        'bonus_threshold_notified'  => 'boolean',
        'sla_warning_notified'      => 'boolean',
        'picked_up_at'              => 'datetime',
        'sla_deadline'              => 'datetime',
        'customer_delay_started_at' => 'datetime',
        'customer_delay_count'      => 'integer',
        'pickup_bonus'              => 'integer',
        'locked_bonus'              => 'integer',
        'final_bonus'               => 'integer',
        'total_delay_seconds'       => 'integer',
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
