<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerTransaction extends Model
{
    use HasFactory;

    protected $table = 'seller_transactions';

    protected $fillable = [
        'seller_id',
        'order_id',
        'seller_order_id',
        'card',
        'type',
        'category',
        'amount',
        'commissionPercent',
        'commissionPrice',
        'netAmount',
        'status',
        'rejected_desc',
        'description',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'seller_order_id' => 'integer',
        'amount' => 'integer',
        'commissionPercent' => 'integer',
        'commissionPrice' => 'integer',
        'netAmount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Status constants ───────────────────────────────────────
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    // ── Relationships ──────────────────────────────────────────
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function sellerOrder()
    {
        return $this->belongsTo(SellerOrder::class, 'seller_order_id');
    }

    // ── Helpers ────────────────────────────────────────────────
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Kutilmoqda',
            self::STATUS_APPROVED => 'Tasdiqlangan',
            self::STATUS_REJECTED => 'Rad etildi',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'muted',
        };
    }
}
