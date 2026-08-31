<?php

namespace App\Models;

use App\Enums\OrderKind;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\PostalReturnStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Sold extends Model
{
    use HasFactory;

    protected $fillable = [
        'deliveryType', 'items', 'user_id', 'courier_id', 'courierName',
        'amount', 'status', 'address', 'qr', 'gift',
        'gift_certificate_id', 'giftCertAmount',
        'buyerWish', 'promocode', 'discountAmount', 'collectionDiscountAmount',
        'is_instore',
        'withCashback', 'cashbackAmount',
        'awarded_cashback_amount', 'cashback_awarded_at',
        'cashback_ready_at', 'cashback_notified_at',
        'completed_at',
        'deliveryPrice', 'paymentStatus',
        'delivery_zone_rule_id', 'delivery_rule_snapshot',
        'status_code', 'payment_status_code',
        'order_kind', 'postal_return_status',
        'postal_return_fee', 'postal_return_note',
        'resend_source_order_id', 'resend_replacement_order_id',
        'resend_available_at',
        'source_collection_id',
        'order_source',
        // ── Packaging ─────────────────────────────────────────
        'with_packaging',
        'packaging_price',
        // ── Boshqasiga sovg'a ──────────────────────────────────
        'is_gift_to_other',
        'recipient_phone',
        'recipient_name',
        'recipient_region',
        'recipient_address',
        'cancel_reason_code', 'cancel_note_uz', 'cancel_note_ru', 'cancel_note_en', 'cancel_note_ja',
        'cancelled_by_seller_id', 'refund_total_amount',
    ];

    protected $casts = [
        'address'         => 'array',
        'items'           => 'array',
        'is_instore'      => 'boolean',
        'withCashback'    => 'boolean',
        'cashback_awarded_at' => 'datetime',
        'cashback_ready_at' => 'datetime',
        'cashback_notified_at' => 'datetime',
        'completed_at' => 'datetime',
        'resend_available_at' => 'datetime',
        'delivery_rule_snapshot' => 'array',
        'with_packaging'  => 'boolean',
        'is_gift_to_other'=> 'boolean',
        'packaging_price' => 'integer',
        'postal_return_fee' => 'integer',
        'refund_total_amount' => 'integer',
        'collectionDiscountAmount' => 'integer',
    ];

    public function getStatusCodeAttribute(?string $value): string
    {
        return $value ?: OrderStatusCode::fromLegacy($this->attributes['status'] ?? null)->value;
    }

    public static function normalizeDeliveryTypeValue(mixed $value): string
    {
        $deliveryType = Str::of((string) $value)->lower()->squish()->value();

        if ($deliveryType === '') {
            return 'delivery';
        }

        if (in_array($deliveryType, ['pickup', 'instore', 'in_store', 'store_pickup'], true)) {
            return 'pickup';
        }

        if (in_array($deliveryType, ['postal', 'mail_service', 'uzpost'], true)) {
            return 'postal';
        }

        if (str_contains($deliveryType, 'pochta') || str_contains($deliveryType, 'mail') || str_contains($deliveryType, 'post')) {
            return 'postal';
        }

        if (in_array($deliveryType, ['delivery', 'courier_service', 'courier', 'kuryer'], true)) {
            return 'delivery';
        }

        return $deliveryType;
    }

    public function getDeliveryTypeAttribute(?string $value): string
    {
        return self::normalizeDeliveryTypeValue($value);
    }

    public function getPaymentStatusCodeAttribute(?string $value): string
    {
        return $value ?: PaymentStatusCode::fromLegacy($this->attributes['paymentStatus'] ?? null)->value;
    }

    public function getOrderKindAttribute(?string $value): string
    {
        return $value ?: OrderKind::STANDARD->value;
    }

    public function getPostalReturnStatusAttribute(?string $value): string
    {
        return $value ?: PostalReturnStatus::NONE->value;
    }

    public static function isCustomerCompletedState(string|int|null $statusCode, mixed $deliveryType = null): bool
    {
        $normalizedStatus = OrderStatusCode::fromLegacy($statusCode)->value;
        $normalizedDeliveryType = self::normalizeDeliveryTypeValue($deliveryType);

        if ($normalizedStatus === OrderStatusCode::CUSTOMER_RECEIVED->value) {
            return true;
        }

        if ($normalizedStatus === OrderStatusCode::DELIVERED->value) {
            return $normalizedDeliveryType !== 'postal';
        }

        return false;
    }

    public static function isCompletedPaidState(
        string|int|null $statusCode,
        string|int|null $paymentStatusCode,
        mixed $deliveryType = null,
    ): bool {
        return self::isCustomerCompletedState($statusCode, $deliveryType)
            && PaymentStatusCode::fromLegacy($paymentStatusCode)->value === PaymentStatusCode::PAID->value;
    }

    public function isCompletedAndPaid(): bool
    {
        return self::isCompletedPaidState(
            $this->status_code,
            $this->payment_status_code,
            $this->deliveryType,
        );
    }

    public function estimatedDeliveryAt(): ?Carbon
    {
        if (!$this->created_at) {
            return null;
        }

        $etaDays = (int) data_get($this->delivery_rule_snapshot, 'eta_days', -1);
        if ($etaDays < 0) {
            $etaDays = match ($this->deliveryType) {
                'pickup', 'instore' => 0,
                'postal' => 3,
                default => 1,
            };
        }

        return $this->created_at->copy()->addDays(max(0, $etaDays));
    }

    public function isDeliveryDelayed(): bool
    {
        $eta = $this->estimatedDeliveryAt();
        if (!$eta) {
            return false;
        }

        if (in_array($this->status_code, [
            OrderStatusCode::CANCELLED->value,
            OrderStatusCode::RETURNED->value,
        ], true)) {
            return false;
        }

        if (self::isCustomerCompletedState($this->status_code, $this->deliveryType)) {
            return false;
        }

        return now()->greaterThan($eta);
    }

    public function isPostalResendSource(): bool
    {
        return $this->postal_return_status === PostalReturnStatus::RETURNED_TO_SENDER->value
            && $this->deliveryType === 'postal'
            && empty($this->resend_replacement_order_id);
    }

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

    public function resendSource(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resend_source_order_id');
    }

    public function resendReplacement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resend_replacement_order_id');
    }

    public function sourceCollection(): BelongsTo
    {
        return $this->belongsTo(CuratedCollection::class, 'source_collection_id');
    }

    public function fulfillment()
    {
        return $this->hasOne(OrderFulfillment::class, 'order_id');
    }

    public function courierTasks()
    {
        return $this->hasMany(CourierTask::class, 'order_id');
    }
}
