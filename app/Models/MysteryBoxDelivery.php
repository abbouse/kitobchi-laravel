<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MysteryBoxDelivery extends Model
{

    protected $fillable = [
        'subscription_id', 'month_number', 'book_ids',
        'status', 'tracking_note',
        'prepared_at', 'shipped_at', 'delivered_at',
    ];

    protected $casts = [
        'book_ids'     => 'array',
        'month_number' => 'integer',
        'prepared_at'  => 'datetime',
        'shipped_at'   => 'datetime',
        'delivered_at' => 'datetime',
    ];

    const STATUS_PENDING   = 'pending';
    const STATUS_PREPARING = 'preparing';
    const STATUS_SHIPPED   = 'shipped';
    const STATUS_DELIVERED = 'delivered';

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
            self::STATUS_SHIPPED   => 'Jo\'natildi',
            self::STATUS_DELIVERED => 'Yetkazildi',
            default                => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PREPARING => 'warning',
            self::STATUS_SHIPPED   => 'info',
            self::STATUS_DELIVERED => 'success',
            default                => 'muted',
        };
    }
}