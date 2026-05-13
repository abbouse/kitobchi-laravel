<?php

namespace App\Models;

use App\Enums\CourierTaskLeg;
use App\Enums\CourierTaskStatusCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'fulfillment_id',
        'hub_id',
        'seller_id',
        'courier_id',
        'leg',
        'status_code',
        'is_cod',
        'cash_collect_amount',
        'cod_reserved_at',
        'cod_released_at',
        'wallet_debited_at',
        'cash_reconciled_at',
        'pickup_address',
        'dropoff_address',
        'assigned_at',
        'accepted_at',
        'arrived_at',
        'picked_up_at',
        'dropped_off_at',
        'completed_at',
        'failed_at',
        'fee_amount',
        'settlement_status',
        'settled_at',
        'meta',
    ];

    protected $casts = [
        'pickup_address' => 'array',
        'dropoff_address' => 'array',
        'is_cod' => 'boolean',
        'cash_collect_amount' => 'integer',
        'cod_reserved_at' => 'datetime',
        'cod_released_at' => 'datetime',
        'wallet_debited_at' => 'datetime',
        'cash_reconciled_at' => 'datetime',
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'arrived_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'dropped_off_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'settled_at' => 'datetime',
        'meta' => 'array',
    ];

    public function getLegAttribute(?string $value): string
    {
        return $value ?: CourierTaskLeg::FIRST_MILE->value;
    }

    public function getStatusCodeAttribute(?string $value): string
    {
        return $value ?: CourierTaskStatusCode::ASSIGNED->value;
    }

    public function order()
    {
        return $this->belongsTo(Sold::class, 'order_id');
    }

    public function fulfillment()
    {
        return $this->belongsTo(OrderFulfillment::class, 'fulfillment_id');
    }

    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function courier()
    {
        return $this->belongsTo(Couriers::class, 'courier_id');
    }
}
