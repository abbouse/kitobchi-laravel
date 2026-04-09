<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MysteryBoxSubscription extends Model
{

    protected $fillable = [
        'user_id', 'plan_id', 'address', 'status',
        'total_months', 'books_per_month', 'price_uzs',
        'delivered_months', 'next_delivery_at',
        'paid_at', 'started_at', 'ends_at', 'cancelled_at',
    ];

    protected $casts = [
        'address'          => 'array', // JSON manzil
        'total_months'     => 'integer',
        'books_per_month'  => 'integer',
        'delivered_months' => 'integer',
        'price_uzs'        => 'integer',
        'paid_at'          => 'datetime',
        'started_at'       => 'datetime',
        'ends_at'          => 'datetime',
        'cancelled_at'     => 'datetime',
        'next_delivery_at' => 'datetime',
    ];

    const STATUS_PENDING   = 'pending_payment';
    const STATUS_ACTIVE    = 'active';
    const STATUS_PAUSED    = 'paused';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    public function user() { return $this->belongsTo(User::class); }
    public function plan() { return $this->belongsTo(MysteryBoxPlan::class, 'plan_id'); }

    public function deliveries()
    {
        return $this->hasMany(MysteryBoxDelivery::class, 'subscription_id');
    }

    public function currentDelivery()
    {
        return $this->hasOne(MysteryBoxDelivery::class, 'subscription_id')
            ->where('status', '!=', 'delivered')
            ->orderBy('month_number');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING   => 'To\'lov kutilmoqda',
            self::STATUS_ACTIVE    => 'Faol',
            self::STATUS_PAUSED    => 'To\'xtatilgan',
            self::STATUS_CANCELLED => 'Bekor qilindi',
            self::STATUS_COMPLETED => 'Yakunlandi',
            default                => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE    => 'success',
            self::STATUS_PAUSED    => 'warning',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_COMPLETED => 'info',
            default                => 'muted',
        };
    }

    public function getRemainingMonthsAttribute(): int
    {
        return max(0, $this->total_months - $this->delivered_months);
    }

    public function getProgressPctAttribute(): int
    {
        if (!$this->total_months) return 0;
        return (int)(($this->delivered_months / $this->total_months) * 100);
    }

    // Keyingi oyni yaratish (delivery record)
    public function createNextDelivery(): ?MysteryBoxDelivery
    {
        if ($this->delivered_months >= $this->total_months) return null;

        return $this->deliveries()->create([
            'month_number' => $this->delivered_months + 1,
            'status'       => 'pending',
        ]);
    }
}