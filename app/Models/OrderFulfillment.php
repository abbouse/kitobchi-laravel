<?php

namespace App\Models;

use App\Enums\FulfillmentStatusCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderFulfillment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'hub_id',
        'fulfillment_mode',
        'status_code',
        'first_mile_mode',
        'last_mile_mode',
        'delivery_service_id',
        'delivery_zone_rule_id',
        'routing_version',
        'is_cod',
        'cash_collect_amount',
        'routing_snapshot',
        'seller_prepared_at',
        'ready_for_pickup_at',
        'picked_from_seller_at',
        'arrived_at_hub_at',
        'qc_checked_at',
        'packed_at',
        'labeled_at',
        'dispatched_to_post_at',
        'assigned_last_mile_at',
        'out_for_delivery_at',
        'delivered_at',
        'returned_at',
        'postal_tracking_number',
        'postal_provider',
        'postal_office_address',
        'label_code',
        'notes',
        'meta',
    ];

    protected $casts = [
        'seller_prepared_at' => 'datetime',
        'ready_for_pickup_at' => 'datetime',
        'is_cod' => 'boolean',
        'cash_collect_amount' => 'integer',
        'routing_snapshot' => 'array',
        'picked_from_seller_at' => 'datetime',
        'arrived_at_hub_at' => 'datetime',
        'qc_checked_at' => 'datetime',
        'packed_at' => 'datetime',
        'labeled_at' => 'datetime',
        'dispatched_to_post_at' => 'datetime',
        'assigned_last_mile_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
        'notes' => 'array',
        'meta' => 'array',
    ];

    public function getStatusCodeAttribute(?string $value): string
    {
        return $value ?: FulfillmentStatusCode::AWAITING_SELLER_PREP->value;
    }

    public function order()
    {
        return $this->belongsTo(Sold::class, 'order_id');
    }

    public function hub()
    {
        return $this->belongsTo(Hub::class);
    }

    public function courierTasks()
    {
        return $this->hasMany(CourierTask::class, 'fulfillment_id');
    }
}
