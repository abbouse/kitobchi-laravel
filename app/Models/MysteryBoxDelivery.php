<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MysteryBoxDelivery extends Model
{

    protected $fillable = [
        'subscription_id', 'month_number', 'dispatch_type', 'book_ids',
        'selection_mode', 'status', 'tracking_note', 'selection_meta',
        'planned_for_date', 'prepared_at', 'ready_at', 'shipped_at',
        'arrived_to_post_at', 'out_for_delivery_at', 'delivered_at',
        'customer_received_at', 'cancelled_at',
    ];

    protected $casts = [
        'book_ids'     => 'array',
        'selection_meta' => 'array',
        'month_number' => 'integer',
        'planned_for_date' => 'date',
        'prepared_at'  => 'datetime',
        'ready_at' => 'datetime',
        'shipped_at'   => 'datetime',
        'arrived_to_post_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'customer_received_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    const DISPATCH_COURIER = 'courier';
    const DISPATCH_POSTAL = 'postal';
    const DISPATCH_PICKUP = 'pickup';

    const STATUS_PENDING   = 'pending';
    const STATUS_PREPARING = 'preparing';
    const STATUS_READY_TO_SHIP = 'ready_to_ship';
    const STATUS_SHIPPED   = 'shipped';
    const STATUS_ARRIVED_TO_POST = 'arrived_to_post';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CUSTOMER_RECEIVED = 'customer_received';
    const STATUS_CANCELLED = 'cancelled';

    public const FINAL_STATUSES = [
        self::STATUS_CUSTOMER_RECEIVED,
        self::STATUS_CANCELLED,
    ];

    public function subscription()
    {
        return $this->belongsTo(MysteryBoxSubscription::class, 'subscription_id');
    }

    public function books()
    {
        // book_ids JSON array dan kitoblarni yuklaymiz
        return Books::whereIn('id', $this->book_ids ?? [])->get();
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING   => 'Kutilmoqda',
            self::STATUS_PREPARING => 'Tayyorlanmoqda',
            self::STATUS_READY_TO_SHIP => 'Jo\'natishga tayyor',
            self::STATUS_SHIPPED   => 'Jo\'natildi',
            self::STATUS_ARRIVED_TO_POST => 'Pochtaga yetib bordi',
            self::STATUS_OUT_FOR_DELIVERY => 'Kuryer yo\'lda',
            self::STATUS_DELIVERED => $this->dispatch_type === self::DISPATCH_PICKUP
                ? 'Olib ketishga tayyor'
                : 'Yetkazildi',
            self::STATUS_CUSTOMER_RECEIVED => 'Mijoz qabul qildi',
            self::STATUS_CANCELLED => 'Bekor qilindi',
            default                => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PREPARING => 'warning',
            self::STATUS_READY_TO_SHIP => 'warning',
            self::STATUS_SHIPPED   => 'info',
            self::STATUS_ARRIVED_TO_POST => 'info',
            self::STATUS_OUT_FOR_DELIVERY => 'info',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_CUSTOMER_RECEIVED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default                => 'muted',
        };
    }

    public function getDispatchTypeLabelAttribute(): string
    {
        return match ($this->dispatch_type) {
            self::DISPATCH_POSTAL => 'Pochta orqali',
            self::DISPATCH_PICKUP => 'Olib ketish',
            default => 'Kuryer orqali',
        };
    }

    public function getIsFinalAttribute(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }

    public function scopeOpsQueue($query)
    {
        return $query
            ->whereNotIn('status', self::FINAL_STATUSES)
            ->whereDate('planned_for_date', '<=', now()->toDateString());
    }
}
