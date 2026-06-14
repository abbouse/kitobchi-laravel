<?php

namespace App\Services;

use App\Enums\CourierOrderStatusCode;
use App\Enums\OrderKind;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\PostalReturnStatus;
use App\Enums\SellerOrderStatusCode;
use App\Models\CourierOrder;
use App\Models\CourierOrderItem;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\Sold;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostalResendService
{
    private const PLATFORM_SELLER_ID = 1;

    public function markReturnedToSender(Sold $order, int $fee, ?string $note = null): void
    {
        if ((string) $order->deliveryType !== 'postal') {
            throw new \RuntimeException("Faqat pochta orqali buyurtma qayta yuborish oqimiga o'tkazilishi mumkin.");
        }

        DB::transaction(function () use ($order, $fee, $note) {
            $lockedOrder = Sold::query()->lockForUpdate()->findOrFail($order->id);

            $lockedOrder->forceFill([
                'status' => OrderStatusCode::RETURNED->legacy(),
                'status_code' => OrderStatusCode::RETURNED->value,
                'postal_return_status' => PostalReturnStatus::RETURNED_TO_SENDER->value,
                'postal_return_fee' => max(0, $fee),
                'postal_return_note' => $note,
                'resend_available_at' => now(),
                'completed_at' => null,
            ])->save();

            CourierOrder::query()
                ->where('order_id', $lockedOrder->id)
                ->update([
                    'status' => CourierOrderStatusCode::RETURNED->legacy(),
                    'status_code' => CourierOrderStatusCode::RETURNED->value,
                    'updated_at' => now(),
                ]);
        });
    }

    public function createResendOrder(Sold $sourceOrder, User $user): Sold
    {
        if ($sourceOrder->user_id !== $user->id) {
            throw new \RuntimeException('Buyurtma foydalanuvchiga tegishli emas.');
        }

        if (!$sourceOrder->isPostalResendSource()) {
            throw new \RuntimeException("Bu buyurtma uchun qayta yuborish hozircha mavjud emas.");
        }

        return DB::transaction(function () use ($sourceOrder) {
            $lockedSource = Sold::query()->lockForUpdate()->findOrFail($sourceOrder->id);

            if (!$lockedSource->isPostalResendSource()) {
                throw new \RuntimeException("Qayta yuborish bu buyurtma uchun allaqachon ishlatilgan.");
            }

            $penaltyFee = (int) ($lockedSource->postal_return_fee ?? 0);
            if ($penaltyFee <= 0) {
                throw new \RuntimeException("Qayta yuborish narxi hali admin tomonidan kiritilmagan.");
            }

            $resendOrder = Sold::create([
                'user_id' => (int) $lockedSource->user_id,
                'qr' => Str::random(40),
                'items' => $lockedSource->items ?? [],
                'address' => $lockedSource->address ?? [],
                'deliveryType' => (string) $lockedSource->deliveryType,
                'deliveryPrice' => $penaltyFee,
                'paymentStatus' => PaymentStatusCode::CARD_PENDING->legacy(),
                'payment_status_code' => PaymentStatusCode::CARD_PENDING->value,
                'amount' => $penaltyFee,
                'gift' => $lockedSource->gift,
                'buyerWish' => $lockedSource->buyerWish,
                'promocode' => null,
                'discountAmount' => 0,
                'withCashback' => false,
                'cashbackAmount' => 0,
                'gift_certificate_id' => null,
                'giftCertAmount' => 0,
                'is_gift_to_other' => (bool) $lockedSource->is_gift_to_other,
                'with_packaging' => false,
                'packaging_price' => 0,
                'recipient_phone' => $lockedSource->recipient_phone,
                'recipient_name' => $lockedSource->recipient_name,
                'recipient_region' => $lockedSource->recipient_region,
                'recipient_address' => $lockedSource->recipient_address,
                'status' => OrderStatusCode::PENDING->legacy(),
                'status_code' => OrderStatusCode::PENDING->value,
                'order_kind' => OrderKind::POSTAL_RESEND->value,
                'resend_source_order_id' => (int) $lockedSource->id,
                'postal_return_status' => PostalReturnStatus::RESEND_PENDING_PAYMENT->value,
                'postal_return_fee' => $penaltyFee,
                'postal_return_note' => $lockedSource->postal_return_note,
            ]);

            // Kitobchi platformasining sovg'asi Sold snapshotida qoladi,
            // lekin seller yoki kuryer yig'ish ro'yxatiga kirmaydi.
            $operationalItems = collect($lockedSource->items ?? [])
                ->reject(fn ($item) => (string) ($item['type'] ?? '') === 'gift'
                    && (int) ($item['seller_id'] ?? 0) === self::PLATFORM_SELLER_ID);

            $groupedItems = $operationalItems
                ->groupBy(fn ($item) => (int) ($item['seller_id'] ?? 0));

            foreach ($groupedItems as $sellerId => $items) {
                if ($sellerId <= 0) {
                    continue;
                }

                $sellerOrder = SellerOrder::create([
                    'seller_id' => $sellerId,
                    'order_id' => (int) $resendOrder->id,
                    'client_id' => (int) $resendOrder->user_id,
                    'status' => SellerOrderStatusCode::PAYMENT_PENDING->legacy(),
                    'status_code' => SellerOrderStatusCode::PAYMENT_PENDING->value,
                    'delivery_type' => (string) $lockedSource->deliveryType,
                    'address' => $lockedSource->address ?? [],
                    'amount' => 0,
                ]);

                foreach ($items as $item) {
                    SellerOrderItem::create([
                        'seller_id' => $sellerId,
                        'order_id' => (int) $sellerOrder->id,
                        'product_id' => (int) ($item['item_id'] ?? 0),
                        'type' => (string) ($item['type'] ?? 'book'),
                        'quantity' => (int) ($item['count_item'] ?? 1),
                        'price' => 0,
                        'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                    ]);
                }
            }

            $courierOrder = CourierOrder::create([
                'courier_id' => null,
                'order_id' => (int) $resendOrder->id,
                'user_id' => (int) $resendOrder->user_id,
                'amount' => $penaltyFee,
                'status' => CourierOrderStatusCode::PAYMENT_PENDING->legacy(),
                'status_code' => CourierOrderStatusCode::PAYMENT_PENDING->value,
                'courierPrice' => $penaltyFee,
                'courierBonus' => 0,
                'pickup_bonus' => 0,
                'locked_bonus' => null,
                'final_bonus' => null,
            ]);

            foreach ($groupedItems as $sellerId => $items) {
                if ($sellerId <= 0) {
                    continue;
                }

                $sellerLocation = DB::table('seller_locations')
                    ->where('seller_id', $sellerId)
                    ->where('is_main', true)
                    ->first();

                if (!$sellerLocation) {
                    continue;
                }

                foreach ($items as $item) {
                    CourierOrderItem::create([
                        'seller_id' => $sellerId,
                        'seller_location_id' => (int) $sellerLocation->id,
                        'order_id' => (int) $resendOrder->id,
                        'type' => (string) ($item['type'] ?? 'book'),
                        'product_id' => (int) ($item['item_id'] ?? 0),
                        'quantity' => (int) ($item['count_item'] ?? 1),
                        'price' => 0,
                        'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                    ]);
                }
            }

            $lockedSource->forceFill([
                'postal_return_status' => PostalReturnStatus::RESEND_PENDING_PAYMENT->value,
                'resend_replacement_order_id' => (int) $resendOrder->id,
            ])->save();

            return $resendOrder;
        });
    }

    public function activatePaidResendOrder(Sold $order): void
    {
        if ($order->order_kind !== OrderKind::POSTAL_RESEND->value) {
            return;
        }

        DB::transaction(function () use ($order) {
            $lockedOrder = Sold::query()->lockForUpdate()->findOrFail($order->id);

            $lockedOrder->forceFill([
                'status' => OrderStatusCode::IN_DELIVERY->legacy(),
                'status_code' => OrderStatusCode::IN_DELIVERY->value,
                'postal_return_status' => PostalReturnStatus::RESENT->value,
            ])->save();

            SellerOrder::query()
                ->where('order_id', $lockedOrder->id)
                ->update([
                    'status' => SellerOrderStatusCode::HANDED_TO_COURIER->legacy(),
                    'status_code' => SellerOrderStatusCode::HANDED_TO_COURIER->value,
                    'updated_at' => now(),
                ]);

            CourierOrder::query()
                ->where('order_id', $lockedOrder->id)
                ->update([
                    'status' => CourierOrderStatusCode::PENDING->legacy(),
                    'status_code' => CourierOrderStatusCode::PENDING->value,
                    'updated_at' => now(),
                ]);

            if ($lockedOrder->resend_source_order_id) {
                Sold::query()->where('id', $lockedOrder->resend_source_order_id)->update([
                    'postal_return_status' => PostalReturnStatus::RESENT->value,
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
