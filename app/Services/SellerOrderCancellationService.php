<?php

namespace App\Services;

use App\Enums\CourierTaskStatusCode;
use App\Enums\FulfillmentStatusCode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Models\Admin;
use App\Models\Couriers;
use App\Models\CourierTask;
use App\Models\GiftCertificate;
use App\Models\OrderItemFinancialSnapshot;
use App\Models\OrderRefund;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\Sold;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SellerOrderCancellationService
{
    public function __construct(
        private readonly OrderFinancialSnapshotService $snapshotService,
        private readonly PaylovP2pRefundService $paylovP2pRefundService,
        private readonly CashbackHistoryService $cashbackHistoryService,
        private readonly OrderService $orderService,
        private readonly OrderStatusPushService $orderStatusPushService,
    ) {}

    public function cancelItem(Seller $seller, SellerOrderItem $item, string $reasonCode, ?string $customNote = null): array
    {
        return $this->requestItemCancellation($seller, $item, $reasonCode, $customNote);
    }

    public function cancelItemByAdmin(Admin $admin, SellerOrderItem $item, string $reasonCode, ?string $customNote = null): array
    {
        return $this->cancelItemFlow(
            item: $item,
            reasonCode: $reasonCode,
            customNote: $customNote,
            seller: null,
            admin: $admin,
            enforceOwnership: false,
        );
    }

    private function cancelItemFlow(
        SellerOrderItem $item,
        string $reasonCode,
        ?string $customNote,
        ?Seller $seller,
        ?Admin $admin,
        bool $enforceOwnership,
    ): array {
        $sellerOrder = SellerOrder::query()->findOrFail($item->order_id);
        $order = Sold::query()->findOrFail($sellerOrder->order_id);

        if ($enforceOwnership && $seller) {
            $this->assertSellerOwnsOrder($seller, $sellerOrder);
        }
        if ($seller) {
            $this->assertSellerOrderAcceptedBeforeCancel($sellerOrder);
        }
        $this->assertOrderCancelable($sellerOrder, $order);

        if ($item->cancelled_at) {
            throw new RuntimeException('Bu mahsulot allaqachon bekor qilingan.');
        }

        if (! $this->hasOtherCourierVisibleSellerOrderItem($sellerOrder, $item)) {
            return $this->cancelSellerOrderFlow(
                sellerOrder: $sellerOrder,
                reasonCode: 'all_products_out_of_stock',
                customNote: $customNote,
                seller: $seller,
                admin: $admin,
                enforceOwnership: $enforceOwnership,
            );
        }

        $reason = SellerCancellationReasonCatalog::itemReasonPayload($reasonCode, $customNote);
        $this->snapshotService->ensureSnapshotsForOrder($order);

        /** @var OrderItemFinancialSnapshot|null $snapshot */
        $snapshot = OrderItemFinancialSnapshot::query()
            ->where('seller_order_item_id', $item->id)
            ->first();

        if (! $snapshot) {
            throw new RuntimeException('Refund snapshot topilmadi.');
        }

        $refund = DB::transaction(function () use ($seller, $admin, $item, $sellerOrder, $order, $snapshot, $reason) {
            $cardRefund = $this->shouldRefundToCard($order) ? (int) $snapshot->card_paid_allocated : 0;
            $cashbackRestore = (int) $snapshot->cashback_allocated;
            $giftRestore = (int) $snapshot->gift_cert_allocated;

            $providerPayload = null;
            if ($cardRefund > 0) {
                $providerPayload = $this->partialCardRefund($order, $cardRefund, [
                    'mode' => 'item',
                    'seller_order_item_id' => $item->id,
                ]);
            }

            if ($cashbackRestore > 0) {
                $this->restoreCashback($order, $cashbackRestore, [
                    'scope' => 'seller_item_cancel',
                    'seller_order_item_id' => $item->id,
                ]);
            }

            if ($giftRestore > 0) {
                $this->restoreGiftCertificate($order, $giftRestore);
            }

            $refund = OrderRefund::query()->create([
                'order_id' => $order->id,
                'seller_order_id' => $sellerOrder->id,
                'seller_order_item_id' => $item->id,
                'seller_id' => $sellerOrder->seller_id,
                'user_id' => $order->user_id,
                'type' => 'partial_item',
                'provider' => $cardRefund > 0 ? 'paylov_p2p' : 'internal_only',
                'card_refund_amount' => $cardRefund,
                'cashback_restore_amount' => $cashbackRestore,
                'gift_cert_restore_amount' => $giftRestore,
                'total_customer_value' => max(0, (int) $snapshot->gross_amount - (int) $snapshot->promo_allocated),
                'status' => 'completed',
                'provider_transaction_id' => data_get($providerPayload, 'transaction_id'),
                'receiver_card_ref' => data_get($providerPayload, 'receiver_card_ref'),
                'reason_code' => $reason['code'],
                'reason_note_uz' => $reason['notes']['uz'],
                'reason_note_ru' => $reason['notes']['ru'],
                'reason_note_en' => $reason['notes']['en'],
                'reason_note_ja' => $reason['notes']['ja'],
                'custom_reason_note' => $reason['custom_note'],
                'provider_payload' => $providerPayload,
                'processed_by_seller_id' => $seller?->id,
                'processed_by_admin_id' => $admin?->id,
                'processed_at' => now(),
            ]);

            $item->forceFill([
                'cancelled_at' => now(),
                'cancelled_by_seller_id' => $seller?->id,
                'cancel_reason_code' => $reason['code'],
                'cancel_note_uz' => $reason['notes']['uz'],
                'cancel_note_ru' => $reason['notes']['ru'],
                'cancel_note_en' => $reason['notes']['en'],
                'cancel_note_ja' => $reason['notes']['ja'],
                'custom_cancel_note' => $reason['custom_note'],
                'refund_status' => 'completed',
                'refunded_at' => now(),
                'cancel_requested_at' => null,
                'cancel_restore_until' => null,
            ])->save();

            $sellerOrder->amount = max(0, (int) $sellerOrder->amount - (int) $snapshot->gross_amount);
            $sellerOrder->save();

            $order->amount = max(0, (int) $order->amount - $this->customerAmountReduction($snapshot));
            $order->refund_total_amount = (int) ($order->refund_total_amount ?? 0) + $cardRefund;
            $order->save();
            $this->syncCodCollectAmount($order);

            $this->mutateOrderItemsJson($order, function (array $row) use ($item, $reason, $refund, $snapshot) {
                if (! $this->jsonRowMatchesItem($row, $item)) {
                    return $row;
                }

                $row['is_cancelled'] = true;
                $row['cancelled_at'] = now()->toIso8601String();
                $row['cancel_reason_code'] = $reason['code'];
                $row['cancel_note_uz'] = $reason['notes']['uz'];
                $row['cancel_note_ru'] = $reason['notes']['ru'];
                $row['cancel_note_en'] = $reason['notes']['en'];
                $row['cancel_note_ja'] = $reason['notes']['ja'];
                $row['refund_status'] = 'completed';
                $row['refund_card_amount'] = (int) $snapshot->card_paid_allocated;
                $row['refund_cashback_amount'] = (int) $snapshot->cashback_allocated;
                $row['refund_gift_cert_amount'] = (int) $snapshot->gift_cert_allocated;
                $row['refund_id'] = $refund->id;
                unset($row['cancel_requested_at'], $row['cancel_restore_until']);

                return $row;
            });

            if (in_array($reason['code'], SellerCancellationReasonCatalog::itemStockZeroReasons(), true)) {
                $this->zeroStockForItem($item);
            }

            if (! $this->activeSellerOrderItemsQuery($sellerOrder)->exists()) {
                $sellerOrder->forceFill([
                    'status' => SellerOrderStatusCode::CANCELLED->legacy(),
                    'status_code' => SellerOrderStatusCode::CANCELLED->value,
                    'cancelled_at' => now(),
                    'cancelled_by_seller_id' => $seller?->id,
                    'cancel_reason_code' => $reason['code'],
                    'cancel_note_uz' => $reason['notes']['uz'],
                    'cancel_note_ru' => $reason['notes']['ru'],
                    'cancel_note_en' => $reason['notes']['en'],
                    'cancel_note_ja' => $reason['notes']['ja'],
                    'custom_cancel_note' => $reason['custom_note'],
                    'refund_status' => 'completed',
                ])->save();
            }

            return $refund;
        });

        return [
            'ok' => true,
            'refund_id' => $refund->id,
            'message' => 'Mahsulot bekor qilindi va refund bajarildi.',
        ];
    }

    public function requestItemCancellation(Seller $seller, SellerOrderItem $item, string $reasonCode, ?string $customNote = null): array
    {
        $sellerOrder = SellerOrder::query()->findOrFail($item->order_id);
        $order = Sold::query()->findOrFail($sellerOrder->order_id);

        $this->assertSellerOwnsOrder($seller, $sellerOrder);
        if ($seller) {
            $this->assertSellerOrderAcceptedBeforeCancel($sellerOrder);
        }
        $this->assertOrderCancelable($sellerOrder, $order);

        if ($item->cancelled_at) {
            throw new RuntimeException('Bu mahsulot allaqachon bekor qilingan.');
        }

        if ($item->refund_status === 'cancel_pending') {
            throw new RuntimeException('Bu mahsulot allaqachon kutish holatida.');
        }

        if (! $this->hasOtherCourierVisibleSellerOrderItem($sellerOrder, $item)) {
            return $this->cancelSellerOrderFlow(
                sellerOrder: $sellerOrder,
                reasonCode: 'all_products_out_of_stock',
                customNote: $customNote,
                seller: $seller,
                admin: null,
                enforceOwnership: true,
            );
        }

        $reason = SellerCancellationReasonCatalog::itemReasonPayload($reasonCode, $customNote);
        $restoreUntil = now()->addMinutes(30);

        DB::transaction(function () use ($seller, $item, $order, $reason, $restoreUntil) {
            $item->forceFill([
                'cancel_requested_at' => now(),
                'cancel_restore_until' => $restoreUntil,
                'cancelled_by_seller_id' => $seller->id,
                'cancel_reason_code' => $reason['code'],
                'cancel_note_uz' => $reason['notes']['uz'],
                'cancel_note_ru' => $reason['notes']['ru'],
                'cancel_note_en' => $reason['notes']['en'],
                'cancel_note_ja' => $reason['notes']['ja'],
                'custom_cancel_note' => $reason['custom_note'],
                'refund_status' => 'cancel_pending',
                'refunded_at' => null,
            ])->save();

            $this->mutateOrderItemsJson($order, function (array $row) use ($item, $reason, $restoreUntil) {
                if (! $this->jsonRowMatchesItem($row, $item)) {
                    return $row;
                }

                $row['is_cancelled'] = false;
                $row['cancel_requested_at'] = now()->toIso8601String();
                $row['cancel_restore_until'] = $restoreUntil->toIso8601String();
                $row['cancel_reason_code'] = $reason['code'];
                $row['cancel_note_uz'] = $reason['notes']['uz'];
                $row['cancel_note_ru'] = $reason['notes']['ru'];
                $row['cancel_note_en'] = $reason['notes']['en'];
                $row['cancel_note_ja'] = $reason['notes']['ja'];
                $row['refund_status'] = 'cancel_pending';

                return $row;
            });

            if (in_array($reason['code'], SellerCancellationReasonCatalog::itemStockZeroReasons(), true)) {
                $this->zeroStockForItem($item);
            }

            $this->syncCodCollectAmount($order);
        });

        return [
            'ok' => true,
            'status' => 'cancel_pending',
            'restore_until' => $restoreUntil->toISOString(),
            'message' => 'Mahsulot vaqtincha bekor qilindi. 30 daqiqa ichida sotuvda mavjud deb qaytarishingiz mumkin.',
        ];
    }

    public function restorePendingItemCancellation(Seller $seller, SellerOrderItem $item): array
    {
        $sellerOrder = SellerOrder::query()->findOrFail($item->order_id);
        $order = Sold::query()->findOrFail($sellerOrder->order_id);

        $this->assertSellerOwnsOrder($seller, $sellerOrder);

        if ($item->refund_status !== 'cancel_pending') {
            throw new RuntimeException('Bu mahsulot kutish holatida emas.');
        }

        if (! $item->cancel_restore_until || $item->cancel_restore_until->isPast()) {
            throw new RuntimeException('30 daqiqalik qaytarish muddati tugagan.');
        }

        $this->assertPendingItemCanBeRestored($sellerOrder, $order);

        DB::transaction(function () use ($item, $order) {
            $this->restoreProductAvailabilityForItem($item);

            $item->forceFill([
                'cancel_requested_at' => null,
                'cancel_restore_until' => null,
                'cancelled_by_seller_id' => null,
                'cancel_reason_code' => null,
                'cancel_note_uz' => null,
                'cancel_note_ru' => null,
                'cancel_note_en' => null,
                'cancel_note_ja' => null,
                'custom_cancel_note' => null,
                'refund_status' => null,
                'refunded_at' => null,
            ])->save();

            $this->mutateOrderItemsJson($order, function (array $row) use ($item) {
                if (! $this->jsonRowMatchesItem($row, $item)) {
                    return $row;
                }

                foreach ([
                    'cancel_requested_at',
                    'cancel_restore_until',
                    'cancel_reason_code',
                    'cancel_note_uz',
                    'cancel_note_ru',
                    'cancel_note_en',
                    'cancel_note_ja',
                    'refund_status',
                ] as $key) {
                    unset($row[$key]);
                }
                $row['is_cancelled'] = false;

                return $row;
            });

            $this->syncCodCollectAmount($order);
        });

        return [
            'ok' => true,
            'status' => 'active',
            'message' => 'Mahsulot sotuvda mavjud deb qaytarildi.',
        ];
    }

    public function finalizePendingItemCancellations(int $limit = 100): int
    {
        $items = SellerOrderItem::query()
            ->where('refund_status', 'cancel_pending')
            ->whereNotNull('cancel_restore_until')
            ->where('cancel_restore_until', '<=', now())
            ->orderBy('cancel_restore_until')
            ->limit($limit)
            ->get();

        $processed = 0;
        $notificationsByOrder = [];
        foreach ($items as $item) {
            try {
                $sellerOrder = SellerOrder::query()->find($item->order_id);
                $orderId = (int) ($sellerOrder?->order_id ?? 0);
                $itemTitle = $this->resolveSellerOrderItemTitle($item, $sellerOrder?->order_id);

                $this->cancelItemFlow(
                    item: $item,
                    reasonCode: (string) $item->cancel_reason_code,
                    customNote: $item->custom_cancel_note,
                    seller: $item->cancelled_by_seller_id ? Seller::query()->find($item->cancelled_by_seller_id) : null,
                    admin: null,
                    enforceOwnership: false,
                );
                $processed++;

                if ($orderId > 0 && $itemTitle !== null && $item->cancelled_by_seller_id) {
                    $notificationsByOrder[$orderId][] = $itemTitle;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        foreach ($notificationsByOrder as $orderId => $titles) {
            $order = Sold::query()->find($orderId);
            if (! $order) {
                continue;
            }

            $this->orderStatusPushService->sendSellerItemsUnavailableNotice($order, $titles);
        }

        return $processed;
    }

    public function cancelSellerOrder(Seller $seller, SellerOrder $sellerOrder, string $reasonCode, ?string $customNote = null): array
    {
        return $this->cancelSellerOrderFlow(
            sellerOrder: $sellerOrder,
            reasonCode: $reasonCode,
            customNote: $customNote,
            seller: $seller,
            admin: null,
            enforceOwnership: true,
        );
    }

    public function cancelSellerOrderByAdmin(Admin $admin, SellerOrder $sellerOrder, string $reasonCode, ?string $customNote = null): array
    {
        return $this->cancelSellerOrderFlow(
            sellerOrder: $sellerOrder,
            reasonCode: $reasonCode,
            customNote: $customNote,
            seller: null,
            admin: $admin,
            enforceOwnership: false,
        );
    }

    private function cancelSellerOrderFlow(
        SellerOrder $sellerOrder,
        string $reasonCode,
        ?string $customNote,
        ?Seller $seller,
        ?Admin $admin,
        bool $enforceOwnership,
    ): array {
        $order = Sold::query()->findOrFail($sellerOrder->order_id);

        if ($enforceOwnership && $seller) {
            $this->assertSellerOwnsOrder($seller, $sellerOrder);
        }
        $this->assertOrderCancelable($sellerOrder, $order);

        $reason = SellerCancellationReasonCatalog::orderReasonPayload($reasonCode, $customNote);
        $this->snapshotService->ensureSnapshotsForOrder($order);

        $activeItems = $this->activeSellerOrderItemsQuery($sellerOrder)->get();
        if ($activeItems->isEmpty()) {
            throw new RuntimeException('Bu seller order ichida bekor qilinadigan faol mahsulot qolmagan.');
        }

        $remainingOtherItems = $this->activeOrderItemsQuery($order)
            ->where('seller_order_items.order_id', '!=', $sellerOrder->id)
            ->count();

        if ($remainingOtherItems === 0) {
            return $this->fullOrderCancelByActor($seller, $admin, $sellerOrder, $order, $activeItems, $reason);
        }

        return $this->partialSellerOrderCancel($seller, $admin, $sellerOrder, $order, $activeItems, $reason);
    }

    private function partialSellerOrderCancel(?Seller $seller, ?Admin $admin, SellerOrder $sellerOrder, Sold $order, Collection $items, array $reason): array
    {
        $snapshots = OrderItemFinancialSnapshot::query()
            ->whereIn('seller_order_item_id', $items->pluck('id')->all())
            ->get()
            ->keyBy('seller_order_item_id');

        return DB::transaction(function () use ($seller, $admin, $sellerOrder, $order, $items, $snapshots, $reason) {
            $cardRefund = 0;
            $cashbackRestore = 0;
            $giftRestore = 0;
            $grossAmount = 0;
            $customerAmountReduction = 0;

            foreach ($items as $item) {
                $snapshot = $snapshots->get($item->id);
                if (! $snapshot) {
                    throw new RuntimeException('Refund snapshot topilmadi.');
                }

                $cardRefund += $this->shouldRefundToCard($order) ? (int) $snapshot->card_paid_allocated : 0;
                $cashbackRestore += (int) $snapshot->cashback_allocated;
                $giftRestore += (int) $snapshot->gift_cert_allocated;
                $grossAmount += (int) $snapshot->gross_amount;
                $customerAmountReduction += $this->customerAmountReduction($snapshot);
            }

            $providerPayload = null;
            if ($cardRefund > 0) {
                $providerPayload = $this->partialCardRefund($order, $cardRefund, [
                    'mode' => 'seller_order',
                    'seller_order_id' => $sellerOrder->id,
                ]);
            }

            if ($cashbackRestore > 0) {
                $this->restoreCashback($order, $cashbackRestore, [
                    'scope' => 'seller_order_cancel',
                    'seller_order_id' => $sellerOrder->id,
                ]);
            }

            if ($giftRestore > 0) {
                $this->restoreGiftCertificate($order, $giftRestore);
            }

            $refund = OrderRefund::query()->create([
                'order_id' => $order->id,
                'seller_order_id' => $sellerOrder->id,
                'seller_id' => $sellerOrder->seller_id,
                'user_id' => $order->user_id,
                'type' => 'seller_order_full',
                'provider' => $cardRefund > 0 ? 'paylov_p2p' : 'internal_only',
                'card_refund_amount' => $cardRefund,
                'cashback_restore_amount' => $cashbackRestore,
                'gift_cert_restore_amount' => $giftRestore,
                'total_customer_value' => max(0, $grossAmount - (int) $snapshots->sum('promo_allocated')),
                'status' => 'completed',
                'provider_transaction_id' => data_get($providerPayload, 'transaction_id'),
                'receiver_card_ref' => data_get($providerPayload, 'receiver_card_ref'),
                'reason_code' => $reason['code'],
                'reason_note_uz' => $reason['notes']['uz'],
                'reason_note_ru' => $reason['notes']['ru'],
                'reason_note_en' => $reason['notes']['en'],
                'reason_note_ja' => $reason['notes']['ja'],
                'custom_reason_note' => $reason['custom_note'],
                'provider_payload' => $providerPayload,
                'processed_by_seller_id' => $seller?->id,
                'processed_by_admin_id' => $admin?->id,
                'processed_at' => now(),
            ]);

            foreach ($items as $item) {
                $snapshot = $snapshots->get($item->id);

                $item->forceFill([
                    'cancelled_at' => now(),
                    'cancelled_by_seller_id' => $seller?->id,
                    'cancel_reason_code' => $reason['code'],
                    'cancel_note_uz' => $reason['notes']['uz'],
                    'cancel_note_ru' => $reason['notes']['ru'],
                    'cancel_note_en' => $reason['notes']['en'],
                    'cancel_note_ja' => $reason['notes']['ja'],
                    'custom_cancel_note' => $reason['custom_note'],
                    'refund_status' => 'completed',
                    'refunded_at' => now(),
                ])->save();

                $this->mutateOrderItemsJson($order, function (array $row) use ($item, $reason, $refund, $snapshot) {
                    if (! $this->jsonRowMatchesItem($row, $item)) {
                        return $row;
                    }

                    $row['is_cancelled'] = true;
                    $row['cancelled_at'] = now()->toIso8601String();
                    $row['cancel_reason_code'] = $reason['code'];
                    $row['cancel_note_uz'] = $reason['notes']['uz'];
                    $row['cancel_note_ru'] = $reason['notes']['ru'];
                    $row['cancel_note_en'] = $reason['notes']['en'];
                    $row['cancel_note_ja'] = $reason['notes']['ja'];
                    $row['refund_status'] = 'completed';
                    $row['refund_card_amount'] = (int) $snapshot->card_paid_allocated;
                    $row['refund_cashback_amount'] = (int) $snapshot->cashback_allocated;
                    $row['refund_gift_cert_amount'] = (int) $snapshot->gift_cert_allocated;
                    $row['refund_id'] = $refund->id;

                    return $row;
                });

                if (in_array($reason['code'], SellerCancellationReasonCatalog::orderStockZeroReasons(), true)) {
                    $this->zeroStockForItem($item);
                }
            }

            $sellerOrder->forceFill([
                'amount' => 0,
                'status' => SellerOrderStatusCode::CANCELLED->legacy(),
                'status_code' => SellerOrderStatusCode::CANCELLED->value,
                'cancelled_at' => now(),
                'cancelled_by_seller_id' => $seller?->id,
                'cancel_reason_code' => $reason['code'],
                'cancel_note_uz' => $reason['notes']['uz'],
                'cancel_note_ru' => $reason['notes']['ru'],
                'cancel_note_en' => $reason['notes']['en'],
                'cancel_note_ja' => $reason['notes']['ja'],
                'custom_cancel_note' => $reason['custom_note'],
                'refund_status' => 'completed',
            ])->save();

            $order->amount = max(0, (int) $order->amount - $customerAmountReduction);
            $order->refund_total_amount = (int) ($order->refund_total_amount ?? 0) + $cardRefund;
            $order->save();
            $this->syncCodCollectAmount($order);

            return [
                'ok' => true,
                'refund_id' => $refund->id,
                'message' => 'Seller order bekor qilindi va refund bajarildi.',
            ];
        });
    }

    private function fullOrderCancelByActor(?Seller $seller, ?Admin $admin, SellerOrder $sellerOrder, Sold $order, Collection $items, array $reason): array
    {
        return DB::transaction(function () use ($seller, $admin, $sellerOrder, $order, $items, $reason) {
            $snapshots = OrderItemFinancialSnapshot::query()
                ->whereIn('seller_order_item_id', $items->pluck('id')->all())
                ->get()
                ->keyBy('seller_order_item_id');

            $transaction = $this->findPaylovTransaction($order);
            $paymentStatus = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
            $isHeldPayment = $paymentStatus === PaymentStatusCode::HELD;
            $cardRefund = $this->shouldRefundToCard($order) ? (int) $order->amount : 0;
            $cashbackRestore = (int) ($order->cashbackAmount ?? 0);
            $giftRestore = (int) ($order->giftCertAmount ?? 0);
            $deliveryRefund = (int) ($order->deliveryPrice ?? 0);
            $packagingRefund = (int) ($order->packaging_price ?? 0);

            $providerPayload = null;
            if ($cardRefund > 0) {
                if (! $transaction) {
                    throw new RuntimeException('Paylov tranzaksiyasi topilmadi.');
                }

                $providerPayload = PaylovService::make()->cancelPayment(
                    (string) ($transaction->provider_transaction_id ?: $transaction->paycom_transaction_id)
                );
            }

            $result = $this->orderService->cancelOrder($order, strict: false);
            if (($result['ok'] ?? false) !== true) {
                throw new RuntimeException((string) ($result['message'] ?? 'Buyurtmani bekor qilib bo‘lmadi.'));
            }

            if ($isHeldPayment && $transaction) {
                $transaction->refresh();
                $providerPayload = data_get($transaction->provider_response, 'dismiss');
            }

            $refund = OrderRefund::query()->create([
                'order_id' => $order->id,
                'seller_order_id' => $sellerOrder->id,
                'seller_id' => $sellerOrder->seller_id,
                'user_id' => $order->user_id,
                'type' => 'full_order',
                'provider' => $isHeldPayment ? 'paylov_hold_dismiss' : ($cardRefund > 0 ? 'paylov_cancel' : 'internal_only'),
                'card_refund_amount' => $cardRefund,
                'cashback_restore_amount' => $cashbackRestore,
                'gift_cert_restore_amount' => $giftRestore,
                'delivery_refund_amount' => $deliveryRefund,
                'packaging_refund_amount' => $packagingRefund,
                'total_customer_value' => $cardRefund + $cashbackRestore + $giftRestore,
                'status' => 'completed',
                'provider_transaction_id' => (string) ($transaction?->provider_transaction_id ?: $transaction?->paycom_transaction_id),
                'reason_code' => $reason['code'],
                'reason_note_uz' => $reason['notes']['uz'],
                'reason_note_ru' => $reason['notes']['ru'],
                'reason_note_en' => $reason['notes']['en'],
                'reason_note_ja' => $reason['notes']['ja'],
                'custom_reason_note' => $reason['custom_note'],
                'provider_payload' => $providerPayload,
                'processed_by_seller_id' => $seller?->id,
                'processed_by_admin_id' => $admin?->id,
                'processed_at' => now(),
            ]);

            $freshOrder = $order->fresh();
            $freshOrder?->forceFill([
                'cancel_reason_code' => $reason['code'],
                'cancel_note_uz' => $reason['notes']['uz'],
                'cancel_note_ru' => $reason['notes']['ru'],
                'cancel_note_en' => $reason['notes']['en'],
                'cancel_note_ja' => $reason['notes']['ja'],
                'cancelled_by_seller_id' => $seller?->id,
                'refund_total_amount' => (int) ($freshOrder->refund_total_amount ?? 0) + $cardRefund,
            ])->save();

            $sellerOrder->forceFill([
                'cancelled_at' => now(),
                'cancelled_by_seller_id' => $seller?->id,
                'cancel_reason_code' => $reason['code'],
                'cancel_note_uz' => $reason['notes']['uz'],
                'cancel_note_ru' => $reason['notes']['ru'],
                'cancel_note_en' => $reason['notes']['en'],
                'cancel_note_ja' => $reason['notes']['ja'],
                'custom_cancel_note' => $reason['custom_note'],
                'refund_status' => 'completed',
            ])->save();

            foreach ($items as $item) {
                $snapshot = $snapshots->get($item->id);

                $item->forceFill([
                    'cancelled_at' => now(),
                    'cancelled_by_seller_id' => $seller?->id,
                    'cancel_reason_code' => $reason['code'],
                    'cancel_note_uz' => $reason['notes']['uz'],
                    'cancel_note_ru' => $reason['notes']['ru'],
                    'cancel_note_en' => $reason['notes']['en'],
                    'cancel_note_ja' => $reason['notes']['ja'],
                    'custom_cancel_note' => $reason['custom_note'],
                    'refund_status' => 'completed',
                    'refunded_at' => now(),
                ])->save();

                if ($freshOrder) {
                    $this->mutateOrderItemsJson($freshOrder, function (array $row) use ($item, $reason, $refund, $snapshot) {
                        if (! $this->jsonRowMatchesItem($row, $item)) {
                            return $row;
                        }

                        $row['is_cancelled'] = true;
                        $row['cancelled_at'] = now()->toIso8601String();
                        $row['cancel_reason_code'] = $reason['code'];
                        $row['cancel_note_uz'] = $reason['notes']['uz'];
                        $row['cancel_note_ru'] = $reason['notes']['ru'];
                        $row['cancel_note_en'] = $reason['notes']['en'];
                        $row['cancel_note_ja'] = $reason['notes']['ja'];
                        $row['refund_status'] = 'completed';
                        $row['refund_card_amount'] = (int) ($snapshot?->card_paid_allocated ?? 0);
                        $row['refund_cashback_amount'] = (int) ($snapshot?->cashback_allocated ?? 0);
                        $row['refund_gift_cert_amount'] = (int) ($snapshot?->gift_cert_allocated ?? 0);
                        $row['refund_id'] = $refund->id;

                        return $row;
                    });
                }
            }

            if (in_array($reason['code'], SellerCancellationReasonCatalog::orderStockZeroReasons(), true)) {
                foreach ($items as $item) {
                    $this->zeroStockForItem($item);
                }
            }

            return [
                'ok' => true,
                'refund_id' => $refund->id,
                'message' => 'Buyurtma to‘liq bekor qilindi va to‘lov qaytarildi.',
            ];
        });
    }

    private function partialCardRefund(Sold $order, int $amount, array $meta): array
    {
        $transaction = $this->findPaylovTransaction($order);
        if (! $transaction) {
            throw new RuntimeException('Paylov tranzaksiyasi topilmadi.');
        }

        return $this->paylovP2pRefundService->refundToOriginalCard($transaction, $amount, $meta);
    }

    private function findPaylovTransaction(Sold $order): ?Transaction
    {
        $query = Transaction::query()
            ->where('order_id', $order->id)
            ->where('payment_type', 'order')
            ->where('provider', 'paylov')
            ->latest('id');

        if ($this->isPaymentAccepted($order)) {
            $paidTransaction = (clone $query)
                ->where(function ($builder) {
                    $builder->where('state', 2)
                        ->orWhereNotNull('perform_time');
                })
                ->first();

            if ($paidTransaction) {
                return $paidTransaction;
            }
        }

        return $query->first();
    }

    private function restoreCashback(Sold $order, int $amount, array $meta = []): void
    {
        if ($amount <= 0 || ! $order->user_id) {
            return;
        }

        $balanceBefore = (int) DB::table('users')->where('id', $order->user_id)->value('cashback');
        DB::table('users')->where('id', $order->user_id)->increment('cashback', $amount);

        $this->cashbackHistoryService->record(
            userId: (int) $order->user_id,
            action: 'refund',
            amount: $amount,
            order: $order,
            balanceBefore: $balanceBefore,
            balanceAfter: $balanceBefore + $amount,
            meta: $meta,
        );
    }

    private function restoreGiftCertificate(Sold $order, int $amount): void
    {
        if ($amount <= 0 || ! $order->gift_certificate_id) {
            return;
        }

        $certId = (int) $order->gift_certificate_id;

        $updatedUsed = DB::table('gift_certificates')
            ->where('id', $certId)
            ->where('status', GiftCertificate::STATUS_USED)
            ->update([
                'status' => GiftCertificate::STATUS_ACTIVE,
                'nominal_uzs' => DB::raw("nominal_uzs + {$amount}"),
                'used_at' => null,
                'updated_at' => now(),
            ]);

        if ($updatedUsed === 0) {
            DB::table('gift_certificates')
                ->where('id', $certId)
                ->where('status', GiftCertificate::STATUS_ACTIVE)
                ->update([
                    'nominal_uzs' => DB::raw("nominal_uzs + {$amount}"),
                    'updated_at' => now(),
                ]);
        }
    }

    public function operationalAmountForCourier(Sold $order): int
    {
        $pendingItemIds = SellerOrderItem::query()
            ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
            ->where('seller_orders.order_id', $order->id)
            ->where('seller_order_items.refund_status', 'cancel_pending')
            ->pluck('seller_order_items.id');

        if ($pendingItemIds->isEmpty()) {
            return max(0, (int) ($order->amount ?? 0));
        }

        $this->snapshotService->ensureSnapshotsForOrder($order);

        $pendingReduction = OrderItemFinancialSnapshot::query()
            ->where('sold_id', $order->id)
            ->whereIn('seller_order_item_id', $pendingItemIds)
            ->get(['gross_amount', 'promo_allocated'])
            ->sum(fn (OrderItemFinancialSnapshot $snapshot) => $this->customerAmountReduction($snapshot));

        return max(0, (int) ($order->amount ?? 0) - (int) $pendingReduction);
    }

    private function syncCodCollectAmount(Sold $order): void
    {
        $order->loadMissing('fulfillment');
        $fulfillment = $order->fulfillment;

        if (! $fulfillment || ! $fulfillment->is_cod) {
            return;
        }

        $newAmount = $this->operationalAmountForCourier($order);
        $oldFulfillmentAmount = max(0, (int) ($fulfillment->cash_collect_amount ?? 0));

        if ($oldFulfillmentAmount !== $newAmount) {
            $fulfillment->forceFill([
                'cash_collect_amount' => $newAmount,
            ])->save();
        }

        $tasks = CourierTask::query()
            ->where('order_id', $order->id)
            ->where('fulfillment_id', $fulfillment->id)
            ->where('is_cod', true)
            ->whereNull('wallet_debited_at')
            ->whereNotIn('status_code', [
                CourierTaskStatusCode::COMPLETED->value,
                CourierTaskStatusCode::CANCELLED->value,
                CourierTaskStatusCode::FAILED->value,
            ])
            ->get();

        foreach ($tasks as $task) {
            DB::transaction(function () use ($task, $newAmount) {
                $lockedTask = CourierTask::query()->lockForUpdate()->findOrFail($task->id);
                $oldAmount = max(0, (int) ($lockedTask->cash_collect_amount ?? 0));

                if ($oldAmount === $newAmount) {
                    return;
                }

                if ($lockedTask->cod_reserved_at && ! $lockedTask->cod_released_at && $lockedTask->courier_id) {
                    $courier = Couriers::query()->lockForUpdate()->find($lockedTask->courier_id);
                    if ($courier) {
                        $diff = $newAmount - $oldAmount;
                        $courier->cod_reserved_amount = max(
                            0,
                            (int) ($courier->cod_reserved_amount ?? 0) + $diff
                        );
                        $courier->save();
                    }
                }

                $lockedTask->cash_collect_amount = $newAmount;
                $lockedTask->save();
            });
        }
    }

    private function customerAmountReduction(OrderItemFinancialSnapshot $snapshot): int
    {
        return max(
            0,
            (int) ($snapshot->gross_amount ?? 0) - (int) ($snapshot->promo_allocated ?? 0)
        );
    }

    private function assertPendingItemCanBeRestored(SellerOrder $sellerOrder, Sold $order): void
    {
        $sellerStatus = $sellerOrder->status_code
            ? SellerOrderStatusCode::fromLegacy($sellerOrder->status_code)
            : SellerOrderStatusCode::fromLegacy($sellerOrder->status);

        if ($sellerStatus === SellerOrderStatusCode::HANDED_TO_COURIER) {
            throw new RuntimeException('Buyurtma kuryerga berilgan. Endi mahsulotni sotuvda mavjud deb qaytarib bo‘lmaydi.');
        }

        $order->loadMissing('fulfillment');
        $fulfillmentStatus = $order->fulfillment?->status_code;
        if (in_array($fulfillmentStatus, [
            FulfillmentStatusCode::PICKED_FROM_SELLER->value,
            FulfillmentStatusCode::ARRIVED_AT_HUB->value,
            FulfillmentStatusCode::QC_CHECKED->value,
            FulfillmentStatusCode::PACKED->value,
            FulfillmentStatusCode::LABELED->value,
            FulfillmentStatusCode::DISPATCHED_TO_POST->value,
            FulfillmentStatusCode::ASSIGNED_LAST_MILE->value,
            FulfillmentStatusCode::OUT_FOR_DELIVERY->value,
            FulfillmentStatusCode::DELIVERED->value,
            FulfillmentStatusCode::RETURNED->value,
            FulfillmentStatusCode::CANCELLED->value,
        ], true)) {
            throw new RuntimeException('Buyurtma kuryer/logistika jarayoniga o‘tgan. Endi mahsulotni sotuvda mavjud deb qaytarib bo‘lmaydi.');
        }

        $hasPickedCourierTask = CourierTask::query()
            ->where('order_id', $order->id)
            ->where('seller_id', $sellerOrder->seller_id)
            ->whereIn('status_code', [
                CourierTaskStatusCode::PICKED_UP->value,
                CourierTaskStatusCode::DROPPED_OFF->value,
                CourierTaskStatusCode::COMPLETED->value,
            ])
            ->exists();

        if ($hasPickedCourierTask) {
            throw new RuntimeException('Kuryer bu buyurtmani olib ketgan. Endi mahsulotni sotuvda mavjud deb qaytarib bo‘lmaydi.');
        }
    }

    private function mutateOrderItemsJson(Sold $order, callable $mutator): void
    {
        $items = collect($order->items ?? [])->map(fn ($row) => $mutator((array) $row))->all();
        $order->items = $items;
        $order->save();
    }

    private function jsonRowMatchesItem(array $row, SellerOrderItem $item): bool
    {
        if ((int) ($row['seller_order_item_id'] ?? $row['id'] ?? 0) === (int) $item->id) {
            return true;
        }

        if ((int) ($row['item_id'] ?? 0) !== (int) $item->product_id) {
            return false;
        }

        if ((string) ($row['type'] ?? '') !== (string) $item->type) {
            return false;
        }

        return (int) ($row['variant_id'] ?? 0) === (int) ($item->variant_id ?? 0);
    }

    private function zeroStockForItem(SellerOrderItem $item): void
    {
        if ($item->type === 'book') {
            DB::table('books')->where('id', $item->product_id)->update([
                'count' => 0,
                'updated_at' => now(),
            ]);

            return;
        }

        if ($item->type === 'stationery') {
            if ($item->variant_id) {
                DB::table('stationery_variants')->where('id', $item->variant_id)->update([
                    'stock' => 0,
                    'updated_at' => now(),
                ]);

                return;
            }

            DB::table('stationeries')->where('id', $item->product_id)->update([
                'stock' => 0,
                'updated_at' => now(),
            ]);
        }
    }

    private function restoreProductAvailabilityForItem(SellerOrderItem $item): void
    {
        $quantity = max(1, (int) $item->quantity);

        if ($item->type === 'book') {
            DB::table('books')
                ->where('id', $item->product_id)
                ->where('count', '<', $quantity)
                ->update([
                    'count' => $quantity,
                    'updated_at' => now(),
                ]);

            return;
        }

        if ($item->type === 'stationery') {
            if ($item->variant_id) {
                DB::table('stationery_variants')
                    ->where('id', $item->variant_id)
                    ->where('stock', '<', $quantity)
                    ->update([
                        'stock' => $quantity,
                        'updated_at' => now(),
                    ]);

                return;
            }

            DB::table('stationeries')
                ->where('id', $item->product_id)
                ->where('stock', '<', $quantity)
                ->update([
                    'stock' => $quantity,
                    'updated_at' => now(),
                ]);
        }
    }

    private function shouldRefundToCard(Sold $order): bool
    {
        if (! $this->isPaymentAccepted($order)) {
            return false;
        }

        if ((int) ($order->amount ?? 0) <= 0) {
            return false;
        }

        return $this->findPaylovTransaction($order) !== null;
    }

    private function isPaidCardOrder(Sold $order): bool
    {
        return $this->shouldRefundToCard($order);
    }

    private function isPaymentAccepted(Sold $order): bool
    {
        return PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus) === PaymentStatusCode::PAID;
    }

    private function assertSellerOwnsOrder(Seller $seller, SellerOrder $sellerOrder): void
    {
        $storeSellerId = $seller->parent_id ?: $seller->id;
        if ((int) $sellerOrder->seller_id !== (int) $storeSellerId) {
            throw new RuntimeException('Bu buyurtma sizning do‘koningizga tegishli emas.');
        }
    }

    private function assertOrderCancelable(SellerOrder $sellerOrder, Sold $order): void
    {
        if (in_array($sellerOrder->status_code, [
            SellerOrderStatusCode::HANDED_TO_COURIER->value,
            SellerOrderStatusCode::CANCELLED->value,
        ], true)) {
            throw new RuntimeException('Bu seller orderni endi bekor qilib bo‘lmaydi.');
        }

        if (in_array($order->status_code, [
            OrderStatusCode::IN_DELIVERY->value,
            OrderStatusCode::DELIVERED->value,
            OrderStatusCode::CUSTOMER_RECEIVED->value,
            OrderStatusCode::RETURNED->value,
            OrderStatusCode::CANCELLED->value,
        ], true)) {
            throw new RuntimeException('Bu buyurtma bosqichida seller bekor qila olmaydi.');
        }
    }

    private function assertSellerOrderAcceptedBeforeCancel(SellerOrder $sellerOrder): void
    {
        $status = SellerOrderStatusCode::fromLegacy($sellerOrder->status_code ?: $sellerOrder->status);

        if ($status === SellerOrderStatusCode::NEW) {
            throw new RuntimeException('Avval buyurtmani qabul qiling, keyin mahsulot yoki buyurtmani bekor qilishingiz mumkin.');
        }
    }

    private function activeOrderItemsQuery(Sold $order)
    {
        return SellerOrderItem::query()
            ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
            ->where('seller_orders.order_id', $order->id)
            ->whereNull('seller_order_items.cancelled_at')
            ->where('seller_order_items.type', '!=', 'gift');
    }

    private function activeSellerOrderItemsQuery(SellerOrder $sellerOrder)
    {
        return SellerOrderItem::query()
            ->where('order_id', $sellerOrder->id)
            ->whereNull('cancelled_at')
            ->where('type', '!=', 'gift');
    }

    private function hasOtherCourierVisibleSellerOrderItem(SellerOrder $sellerOrder, SellerOrderItem $currentItem): bool
    {
        return SellerOrderItem::query()
            ->where('order_id', $sellerOrder->id)
            ->where('id', '!=', $currentItem->id)
            ->whereNull('cancelled_at')
            ->where('type', '!=', 'gift')
            ->where(function ($query) {
                $query->whereNull('refund_status')
                    ->orWhere('refund_status', '!=', 'cancel_pending');
            })
            ->exists();
    }

    private function resolveSellerOrderItemTitle(SellerOrderItem $item, ?int $orderId = null): ?string
    {
        $productPayload = $item->product;
        $parent = is_array($productPayload) ? ($productPayload['parent'] ?? null) : null;
        $variant = is_array($productPayload) ? ($productPayload['variant'] ?? null) : null;

        $title = trim((string) (
            $variant?->name
            ?? $parent?->name
            ?? null
        ));

        if ($title !== '') {
            return $title;
        }

        if ($orderId) {
            $order = Sold::query()->find($orderId);
            $row = collect($order?->items ?? [])
                ->first(fn ($row) => $this->jsonRowMatchesItem((array) $row, $item));

            $fallbackTitle = trim((string) (($row['name'] ?? $row['title'] ?? '')));
            if ($fallbackTitle !== '') {
                return $fallbackTitle;
            }
        }

        return 'Mahsulot';
    }
}
