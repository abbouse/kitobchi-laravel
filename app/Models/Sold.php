<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sold extends Model
{
    use HasFactory;

    protected $fillable = [
        'deliveryType', 'items', 'user_id', 'courier_id', 'courierName',
        'amount', 'status', 'address', 'qr', 'gift',
        'gift_certificate_id', 'giftCertAmount',
        'buyerWish', 'promocode', 'discountAmount',
        'is_instore',
        'withCashback', 'cashbackAmount',
        'awarded_cashback_amount', 'cashback_awarded_at',
        'cashback_ready_at', 'cashback_notified_at',
        'deliveryPrice', 'paymentStatus',
        // ── Packaging ─────────────────────────────────────────
        'with_packaging',
        'packaging_price',
        // ── Boshqasiga sovg'a ──────────────────────────────────
        'is_gift_to_other',
        'recipient_phone',
        'recipient_name',
        'recipient_region',
        'recipient_address',
    ];

    protected $casts = [
        'address'         => 'array',
        'items'           => 'array',
        'is_instore'      => 'boolean',
        'withCashback'    => 'boolean',
        'cashback_awarded_at' => 'datetime',
        'cashback_ready_at' => 'datetime',
        'cashback_notified_at' => 'datetime',
        'with_packaging'  => 'boolean',
        'is_gift_to_other'=> 'boolean',
        'packaging_price' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getGift(): BelongsTo
    {
        return $this->belongsTo(Gifts::class, 'gift', 'id');
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(GiftCertificate::class, 'gift_certificate_id', 'id');
    }
}
