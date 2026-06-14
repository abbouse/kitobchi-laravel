<?php

namespace App\Services;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Models\Admin;
use App\Models\GiftCertificate;
use App\Models\OrderItemFinancialSnapshot;
use App\Models\OrderRefund;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\Sold;
use App\Models\Transaction;
use App\Models\User;
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
    ) {
    }

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
        $this->assertOrderCancelable($sellerOrder, $order);

        if ($item->cancelled_at) {
            throw new RuntimeException('Bu mahsulot allaqachon bekor qilingan.');
        }

        $activeItemCount = $this->activeOrderItemsQuery($order)->count();
        if ($activeItemCount <= 1) {
            throw new RuntimeException('Oxirgi mahsulotni item bo‘yicha bekor qilib bo‘lmaydi. Butun buyurtmani bekor qiling.');
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

            $order->amount = max(0, (int) $order->amount - (int) $snapshot->card_paid_allocated);
            $order->refund_total_amount = (int) ($order->refund_total_amount ?? 0) + $cardRefund;
            $order->save();

            $this->mutateOrderItemsJson($order, function (array $row) use ($item, $reason, $refund, $snapshot) {
                if ((int) ($row['item_id'] ?? 0) !== (int) $item->product_id) {
                    return $row;
                }
                if ((string) ($row['type'] ?? '') !== (string) $item->type) {
                    return $row;
                }
                if ((int) ($row['variant_id'] ?? 0) !== (int) ($item->variant_id ?? 0)) {
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
        $this->assertOrderCancelable($sellerOrder, $order);

        if ($item->cancelled_at) {
            throw new RuntimeException('Bu mahsulot allaqachon bekor qilingan.');
        }

        if ($item->refund_status === 'cancel_pending') {
            throw new RuntimeException('Bu mahsulot allaqachon kutish holatida.');
        }

        $activeItemCount = $this->activeOrderItemsQuery($order)->count();
        if ($activeItemCount <= 1) {
            throw new RuntimeException('Oxirgi mahsulotni item bo‘yicha bekor qilib bo‘lmaydi. Butun buyurtmani bekor qiling.');
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
        foreach ($items as $item) {
            try {
                $this->cancelItemFlow(
                    item: $item,
                    reasonCode: (string) $item->cancel_reason_code,
                    customNote: $item->custom_cancel_note,
                    seller: $item->cancelled_by_seller_id ? Seller::query()->find($item->cancelled_by_seller_id) : null,
                    admin: null,
                    enforceOwnership: false,
                );
                $processed++;
            } catch (\Throwable) {
                continue;
            }
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

            foreach ($items as $item) {
                $snapshot = $snapshots->get($item->id);
                if (! $snapshot) {
                    throw new RuntimeException('Refund snapshot topilmadi.');
                }

                $cardRefund += $this->shouldRefundToCard($order) ? (int) $snapshot->card_paid_allocated : 0;
                $cashbackRestore += (int) $snapshot->cashback_allocated;
                $giftRestore += (int) $snapshot->gift_cert_allocated;
                $grossAmount += (int) $snapshot->gross_amount;
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
                    if ((int) ($row['item_id'] ?? 0) !== (int) $item->product_id) {
                        return $row;
                    }
                    if ((string) ($row['type'] ?? '') !== (string) $item->type) {
                        return $row;
                    }
                    if ((int) ($row['variant_id'] ?? 0) !== (int) ($item->variant_id ?? 0)) {
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

            $order->amount = max(0, (int) $order->amount - $cardRefund);
            $order->refund_total_amount = (int) ($order->refund_total_amount ?? 0) + $cardRefund;
            $order->save();

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
            $cardRefund = $this->isPaidCardOrder($order) ? (int) $order->amount : 0;
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

            $refund = OrderRefund::query()->create([
                'order_id' => $order->id,
                'seller_order_id' => $sellerOrder->id,
                'seller_id' => $sellerOrder->seller_id,
                'user_id' => $order->user_id,
                'type' => 'full_order',
                'provider' => $cardRefund > 0 ? 'paylov_cancel' : 'internal_only',
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
                        if ((int) ($row['item_id'] ?? 0) !== (int) $item->product_id) {
                            return $row;
                        }
                        if ((string) ($row['type'] ?? '') !== (string) $item->type) {
                            return $row;
                        }
                        if ((int) ($row['variant_id'] ?? 0) !== (int) ($item->variant_id ?? 0)) {
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

        if ($this->isPaidCardOrder($order)) {
            $query->where('state', 2);
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
        return $this->isPaidCardOrder($order);
    }

    private function isPaidCardOrder(Sold $order): bool
    {
        return $order->payment_status_code === PaymentStatusCode::PAID->value;
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
}
