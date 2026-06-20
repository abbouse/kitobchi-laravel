<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\FulfillmentMode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Http\Controllers\Controller;
use App\Models\CourierOrder;
use App\Models\Couriers;
use App\Models\OrderFulfillment;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\SellerOrder;
use App\Models\Sold;
use App\Services\AdminOrderStatusSyncService;
use App\Services\CourierBonusService;
use App\Services\CourierTaskOrchestratorService;
use App\Services\OrderRealtimeService;
use App\Services\OrderStatusPushService;
use App\Services\QrTokenService;
use App\Services\SellerCancellationReasonCatalog;
use App\Services\SellerOrderCancellationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private readonly CourierBonusService $courierBonusService,
        private readonly AdminOrderStatusSyncService $statusSync,
        private readonly OrderRealtimeService $orderRealtimeService,
        private readonly QrTokenService $qrTokenService,
        private readonly OrderStatusPushService $orderStatusPushService,
        private readonly CourierTaskOrchestratorService $courierTaskOrchestratorService,
        private readonly SellerOrderCancellationService $sellerOrderCancellationService,
    ) {
        $this->middleware('auth:seller');
    }

    /**
     * ✅ OWNER DO'KON ID QAYTARADI
     */
    private function getStoreSellerId($seller)
    {
        return $seller->parent_id ?: $seller->id;
    }

    /**
     * ✅ ORDER ACCESS: OWNER, ADMIN (1), PRODUCT MANAGER (2)
     */
    private function hasOrderAccess($seller)
    {
        // parent_id = NULL → OWNER → FULL ACCESS
        // parent_id mavjud + role=1 yoki 2 → ACCESS
        return ! $seller->parent_id || in_array($seller->role, [1, 2]);
    }

    private function applySellerStatusFilter($query, SellerOrderStatusCode ...$statuses)
    {
        $values = array_map(fn (SellerOrderStatusCode $status) => $status->value, $statuses);
        $legacyValues = array_map(fn (SellerOrderStatusCode $status) => $status->legacy(), $statuses);

        return $query->where(function ($statusQuery) use ($values, $legacyValues) {
            $statusQuery->whereIn('status_code', $values)
                ->orWhere(function ($fallback) use ($legacyValues) {
                    $fallback->whereNull('status_code')
                        ->whereIn('status', $legacyValues);
                });
        });
    }

    private function applyVisibleSellerOrdersScope($query)
    {
        return $query
            ->where(function ($statusQuery) {
                $statusQuery->whereNotIn('status_code', [
                    SellerOrderStatusCode::PAYMENT_PENDING->value,
                    'pending',
                ])->orWhere(function ($fallback) {
                    $fallback->whereNull('status_code')
                        ->whereNotIn('status', [
                            SellerOrderStatusCode::PAYMENT_PENDING->legacy(),
                            SellerOrderStatusCode::PAYMENT_PENDING->value,
                            'pending',
                        ]);
                });
            })
            ->whereHas('order', function ($paymentQuery) {
                $paymentQuery->where(function ($stateQuery) {
                    $stateQuery->where('payment_status_code', '!=', PaymentStatusCode::CARD_PENDING->value)
                        ->orWhere(function ($fallback) {
                            $fallback->whereNull('payment_status_code')
                                ->whereNotIn('paymentStatus', [
                                    PaymentStatusCode::CARD_PENDING->legacy(),
                                    PaymentStatusCode::CARD_PENDING->value,
                                    'pending',
                                ]);
                        });
                });
            });
    }

    private function assignedLocation($seller): ?SellerLocation
    {
        if (! $seller?->parent_id || ! $seller->seller_location_id) {
            return null;
        }

        return SellerLocation::query()
            ->whereKey($seller->seller_location_id)
            ->where('seller_id', $this->getStoreSellerId($seller))
            ->where('is_deleted', false)
            ->first();
    }

    private function applyStaffBranchScope($query, $seller, string $table = 'seller_orders')
    {
        if (! $seller->parent_id) {
            return $query;
        }

        $location = $this->assignedLocation($seller);
        if (! $location) {
            return $query->whereRaw('1 = 0');
        }

        $jsonLocation = "CAST(JSON_UNQUOTE(JSON_EXTRACT({$table}.address, '$[0].location_id')) AS UNSIGNED)";

        if ($location->is_main) {
            return $query->where(function ($inner) use ($table, $jsonLocation, $location) {
                $inner->whereRaw("{$jsonLocation} = ?", [$location->id])
                    ->orWhere(function ($legacy) use ($table, $jsonLocation) {
                        $legacy->whereRaw("{$jsonLocation} IS NULL")
                            ->where(function ($delivery) use ($table) {
                                $delivery->whereRaw("LOWER(COALESCE({$table}.delivery_type, '')) != ?", ['pickup'])
                                    ->orWhereNull("{$table}.delivery_type");
                            });
                    });
            });
        }

        return $query->whereRaw("{$jsonLocation} = ?", [$location->id]);
    }

    private function staffCanAccessOrder($seller, SellerOrder $order): bool
    {
        if ((int) $order->seller_id !== (int) $this->getStoreSellerId($seller)) {
            return false;
        }

        if (! $seller->parent_id) {
            return true;
        }

        return $this->applyStaffBranchScope(
            SellerOrder::query()->whereKey($order->id),
            $seller
        )->exists();
    }

    private function formatOrderAddress($address): array
    {
        if (is_array($address)) {
            return array_values($address);
        }

        if (is_string($address)) {
            $decoded = json_decode($address, true);

            return is_array($decoded) ? array_values($decoded) : [];
        }

        return [];
    }

    private function formatSellerOrderItem($item): array
    {
        return [
            'id' => (int) $item->id,
            'seller_id' => (int) $item->seller_id,
            'order_id' => (int) $item->order_id,
            'product_id' => (int) $item->product_id,
            'variant_id' => $item->variant_id ? (int) $item->variant_id : null,
            'type' => (string) $item->type,
            'quantity' => (int) $item->quantity,
            'price' => (int) $item->price,
            'created_at' => optional($item->created_at)?->toISOString(),
            'updated_at' => optional($item->updated_at)?->toISOString(),
            'book' => $item->book?->toArray(),
            'stationery' => $item->stationery?->toArray(),
            'gift' => $item->gift?->toArray(),
            'variant' => $item->variant ? [
                'id' => (int) $item->variant->id,
                'color_name' => $item->variant->color_name,
                'image_path' => $item->variant->image_path,
                'stock' => (int) ($item->variant->stock ?? 0),
            ] : null,
            'is_cancelled' => $item->cancelled_at !== null,
            'cancelled_at' => optional($item->cancelled_at)?->toISOString(),
            'cancel_reason_code' => $item->cancel_reason_code,
            'cancel_notes' => [
                'uz' => $item->cancel_note_uz,
                'ru' => $item->cancel_note_ru,
                'en' => $item->cancel_note_en,
                'ja' => $item->cancel_note_ja,
            ],
            'refund_status' => $item->refund_status,
            'refunded_at' => optional($item->refunded_at)?->toISOString(),
            'cancel_requested_at' => optional($item->cancel_requested_at)?->toISOString(),
            'cancel_restore_until' => optional($item->cancel_restore_until)?->toISOString(),
            'can_restore_cancel' => $item->refund_status === 'cancel_pending'
                && $item->cancel_restore_until
                && $item->cancel_restore_until->isFuture(),
        ];
    }

    private function resolveMainOrderStatus($order): ?string
    {
        $mainOrder = $order->order;
        if (! $mainOrder) {
            return null;
        }

        return $mainOrder->status_code ?? OrderStatusCode::fromLegacy($mainOrder->status)->value;
    }

    private function resolveLifecycleStatus($order): string
    {
        $sellerStatus = $order->status_code ?? SellerOrderStatusCode::fromLegacy($order->status)->value;
        $mainStatus = $this->resolveMainOrderStatus($order);

        if ($sellerStatus === SellerOrderStatusCode::CANCELLED->value || $mainStatus === OrderStatusCode::CANCELLED->value) {
            return 'cancelled';
        }

        if ($mainStatus === OrderStatusCode::RETURNED->value) {
            return 'returned';
        }

        if ($mainStatus === OrderStatusCode::DELIVERED->value) {
            return 'delivered';
        }

        if ($mainStatus === OrderStatusCode::CUSTOMER_RECEIVED->value) {
            return 'customer_received';
        }

        if ($mainStatus === OrderStatusCode::IN_DELIVERY->value) {
            return 'in_delivery';
        }

        return $sellerStatus;
    }

    private function buildFulfillmentHint(?OrderFulfillment $fulfillment): ?string
    {
        if (! $fulfillment) {
            return null;
        }

        $hubName = $fulfillment->hub?->name ?: 'Hub';

        return match ($fulfillment->status_code) {
            'awaiting_seller_prep' => 'Buyurtma tayyorlanmoqda',
            'ready_for_pickup' => "Kuryer {$hubName} uchun olib ketadi",
            'picked_from_seller' => "Buyurtma {$hubName}ga olib ketilmoqda",
            'arrived_at_hub' => "{$hubName} buyurtmani qabul qildi",
            'qc_checked' => "{$hubName}da tekshiruv tugadi",
            'packed' => "{$hubName}da qadoqlanmoqda",
            'labeled' => "{$hubName} etiketka yopishtirdi",
            'dispatched_to_post' => "{$hubName} pochtaga topshirdi",
            'assigned_last_mile' => "{$hubName} mijozga yuborishni boshladi",
            'out_for_delivery' => 'Buyurtma mijozga olib borilmoqda',
            'delivered' => 'Buyurtma yetkazish nuqtasiga yetib bordi',
            'customer_received' => 'Mijoz buyurtmani qabul qildi',
            'returned' => 'Buyurtma qaytdi',
            'cancelled' => 'Buyurtma bekor qilindi',
            default => null,
        };
    }

    private function formatSellerOrder($order): array
    {
        $items = collect($order->items ?? [])
            ->map(fn ($item) => $this->formatSellerOrderItem($item))
            ->values()
            ->all();
        $address = $this->formatOrderAddress($order->address);
        $primaryAddress = $address[0] ?? [];
        $mainLocation = $order->seller?->location;
        $isPickup = (string) $order->delivery_type === 'pickup';

        $branch = [
            'id' => isset($primaryAddress['location_id'])
                ? (int) $primaryAddress['location_id']
                : ($isPickup ? null : ($mainLocation?->id ? (int) $mainLocation->id : null)),
            'address' => $primaryAddress['branch_address']
                ?? ($isPickup ? ($primaryAddress['fullAddress'] ?? null) : $mainLocation?->fullAddress),
            'is_main' => array_key_exists('branch_is_main', $primaryAddress)
                ? (bool) $primaryAddress['branch_is_main']
                : ! $isPickup,
        ];

        $fulfillment = $order->order?->fulfillment;
        $lifecycleStatus = $this->resolveLifecycleStatus($order);

        return [
            'id' => (int) $order->id,
            'seller_id' => (int) $order->seller_id,
            'order_id' => $order->order_id ? (int) $order->order_id : null,
            'client_id' => $order->client_id ? (int) $order->client_id : null,
            'courier_id' => $order->courier_id ? (int) $order->courier_id : null,
            'courierName' => $order->courierName,
            'amount' => (int) $order->amount,
            'items_count' => (int) ($order->items_count ?? count($items)),
            'status' => $order->status_code ?? SellerOrderStatusCode::fromLegacy($order->status)->value,
            'status_code' => $order->status_code,
            'main_order_status' => $this->resolveMainOrderStatus($order),
            'lifecycle_status' => $lifecycleStatus,
            'delivery_type' => $order->delivery_type,
            'fulfillment_mode' => $fulfillment?->fulfillment_mode,
            'fulfillment_status' => $fulfillment?->status_code,
            'fulfillment_hint' => $this->buildFulfillmentHint($fulfillment),
            'hub' => $fulfillment?->hub?->only(['id', 'name', 'code']),
            'address' => $address,
            'branch' => $branch,
            'items' => $items,
            'created_at' => optional($order->created_at)?->toISOString(),
            'updated_at' => optional($order->updated_at)?->toISOString(),
            'cancelled_at' => optional($order->cancelled_at)?->toISOString(),
            'cancel_reason_code' => $order->cancel_reason_code,
            'cancel_notes' => [
                'uz' => $order->cancel_note_uz,
                'ru' => $order->cancel_note_ru,
                'en' => $order->cancel_note_en,
                'ja' => $order->cancel_note_ja,
            ],
            'refund_status' => $order->refund_status,
        ];
    }

    public function lastOrders(Request $request)
    {
        $seller = Auth::guard('seller')->user();

        if (! $this->hasOrderAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $scope = (string) $request->query('scope', '');
        $statusFilter = (string) $request->query('status', 'all');

        $query = $this->applyVisibleSellerOrdersScope(
            Seller::find($storeSellerId)->orders()
        )
            ->with([
                'seller.location',
                'order.fulfillment.hub:id,name,code',
                'items' => fn ($q) => $q->where('seller_id', $storeSellerId)
                    ->with(['book', 'stationery', 'gift']),
            ])
            ->withCount(['items' => fn ($q) => $q->where('seller_id', $storeSellerId)]);
        $this->applyStaffBranchScope($query, $seller);

        if ($scope === 'home') {
            $this->applySellerStatusFilter(
                $query,
                SellerOrderStatusCode::ACCEPTED,
                SellerOrderStatusCode::HANDED_TO_COURIER,
            );
        } else {
            $query->when($statusFilter !== 'all', function ($q) use ($statusFilter) {
                $map = [
                    'new' => SellerOrderStatusCode::NEW,
                    'accepted' => SellerOrderStatusCode::ACCEPTED,
                    'handed' => SellerOrderStatusCode::HANDED_TO_COURIER,
                    'cancelled' => SellerOrderStatusCode::CANCELLED,
                ];

                if ($statusFilter === 'delivered') {
                    $q->whereHas('order', function ($orderQuery) {
                        $orderQuery->whereIn('status_code', [
                            OrderStatusCode::DELIVERED->value,
                            OrderStatusCode::CUSTOMER_RECEIVED->value,
                        ])
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('status_code')
                                    ->whereIn('status', [
                                        OrderStatusCode::DELIVERED->legacy(),
                                        OrderStatusCode::CUSTOMER_RECEIVED->legacy(),
                                    ]);
                            });
                    });
                } elseif ($statusFilter === 'returned') {
                    $q->whereHas('order', function ($orderQuery) {
                        $orderQuery->where('status_code', OrderStatusCode::RETURNED->value)
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('status_code')
                                    ->where('status', OrderStatusCode::RETURNED->legacy());
                            });
                    });
                } elseif (array_key_exists($statusFilter, $map)) {
                    $this->applySellerStatusFilter($q, $map[$statusFilter]);
                }
            });
        }

        $orders = $query
            ->orderByRaw("
                CASE
                    WHEN COALESCE(status_code, '') IN ('accepted', 'handed_to_courier') OR (status_code IS NULL AND status IN (2, 3)) THEN 0
                    WHEN COALESCE(status_code, '') = 'new' OR (status_code IS NULL AND status = 1) THEN 1
                    WHEN COALESCE(status_code, '') = 'cancelled' OR (status_code IS NULL AND status = 4) THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('created_at')
            ->limit($scope === 'home' ? 20 : 100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders->map(fn ($order) => $this->formatSellerOrder($order))->values(),
        ]);
    }

    /**
     * QR skanerlanganda — buyurtma ma'lumotlarini qaytaradi (status o'zgarmaydi)
     * GET /orders/scan-qr/{qr}
     */
    public function scanQR(Request $request, $qr)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (! $this->hasOrderAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $parsedQr = $this->parsePickupQr($qr);
        if (! $parsedQr) {
            return response()->json(['success' => false, 'message' => "Noto'g'ri QR format"], 400);
        }
        $sellerId = $parsedQr['seller_id'];
        $orderId = $parsedQr['order_id'];
        $courierId = $parsedQr['courier_id'];

        $storeSellerId = $this->getStoreSellerId($seller);
        if ((string) $storeSellerId !== (string) $sellerId) {
            return response()->json(['success' => false, 'message' => "Do'kon mos kelmadi"], 403);
        }

        $sellerOrder = SellerOrder::with([
            'items' => fn ($q) => $q->where('seller_id', $storeSellerId)
                ->with(['book', 'stationery', 'gift', 'variant']),
        ])
            ->where('order_id', $orderId)
            ->where('seller_id', $storeSellerId)
            ->first();

        if (! $sellerOrder) {
            return response()->json(['success' => false, 'message' => 'Buyurtma topilmadi'], 404);
        }
        if (! $this->staffCanAccessOrder($seller, $sellerOrder)) {
            return response()->json(['success' => false, 'message' => 'Bu buyurtma boshqa filialga tegishli'], 403);
        }

        $courier = Couriers::find($courierId);
        if (! $courier) {
            return response()->json(['success' => false, 'message' => 'Kuryer topilmadi'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $this->formatSellerOrder($sellerOrder),
                'courier' => [
                    'id' => $courier->id,
                    'name' => $courier->first_name.' '.$courier->last_name,
                ],
                'qr' => $parsedQr['normalized_qr'],
            ],
        ]);
    }

    public function toCourier(Request $request, $qr)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (! $this->hasOrderAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Orders available only for Owner, Admin, and Product Manager.',
            ], 403);
        }
        $parsedQr = $this->parsePickupQr($qr);
        if (! $parsedQr) {
            return response()->json(['success' => false, 'message' => 'Invalid QR format'], 400);
        }
        $sellerId = $parsedQr['seller_id'];
        $orderId = $parsedQr['order_id'];
        $courierId = $parsedQr['courier_id'];

        $storeSellerId = $this->getStoreSellerId($seller);
        $storeSeller = Seller::find($storeSellerId);

        if (! $storeSeller || (string) $storeSellerId !== (string) $sellerId) {
            return response()->json(['success' => false, 'message' => 'Shop name does not match'], 403);
        }

        try {
            $response = DB::transaction(function () use ($orderId, $courierId, $storeSellerId, $seller) {
                $sellerOrderQuery = SellerOrder::where('order_id', $orderId)
                    ->where('seller_id', $storeSellerId)
                    ->lockForUpdate();
                $this->applyStaffBranchScope($sellerOrderQuery, $seller);
                $this->applySellerStatusFilter(
                    $sellerOrderQuery,
                    SellerOrderStatusCode::NEW,
                    SellerOrderStatusCode::ACCEPTED,
                );
                $seller_order = $sellerOrderQuery->first();

                if (! $seller_order) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Buyurtma topilmadi yoki allaqachon kuryerga berilgan',
                    ], 404);
                }

                $sold = Sold::where('id', $orderId)->lockForUpdate()->first();
                if (! $sold) {
                    return response()->json(['success' => false, 'message' => 'Order not found in solds table'], 404);
                }

                $hasPendingItemCancellation = \App\Models\SellerOrderItem::query()
                    ->where('order_id', $seller_order->id)
                    ->where('refund_status', 'cancel_pending')
                    ->exists();

                if ($hasPendingItemCancellation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mahsulot bo‘yicha 30 daqiqalik kutish holati bor. Avval uni sotuvda mavjud deb qaytaring yoki muddat tugashini kuting.',
                    ], 422);
                }

                $courier = Couriers::find($courierId);
                if (! $courier) {
                    return response()->json(['success' => false, 'message' => 'Courier not found'], 404);
                }

                $seller_order->courier_id = $courier->id;
                $seller_order->courierName = $courier->first_name.' '.$courier->last_name;
                $seller_order->status = SellerOrderStatusCode::HANDED_TO_COURIER->legacy();
                $seller_order->status_code = SellerOrderStatusCode::HANDED_TO_COURIER->value;
                $seller_order->save();

                $allSellersDone = SellerOrder::where('order_id', $orderId)
                    ->where(function ($query) {
                        $query->where('status_code', '!=', SellerOrderStatusCode::HANDED_TO_COURIER->value)
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('status_code')
                                    ->where('status', '!=', SellerOrderStatusCode::HANDED_TO_COURIER->legacy());
                            });
                    })
                    ->doesntExist();

                $this->courierTaskOrchestratorService->markSellerHandover(
                    order: $sold,
                    sellerId: $storeSellerId,
                    courierId: $courier->id,
                );

                $sold->loadMissing('fulfillment');
                $fulfillmentMode = $sold->fulfillment?->fulfillment_mode;

                if ($allSellersDone) {
                    $previousStatus = (string) $sold->status;

                    if ($fulfillmentMode === FulfillmentMode::DIRECT_COURIER->value) {
                        $sold->status = OrderStatusCode::IN_DELIVERY->legacy();
                        $sold->status_code = OrderStatusCode::IN_DELIVERY->value;
                    } else {
                        $sold->status = OrderStatusCode::PACKING->legacy();
                        $sold->status_code = OrderStatusCode::PACKING->value;
                    }
                    $sold->updated_at = now();
                    $sold->save();

                    $courierOrder = CourierOrder::where('order_id', $orderId)
                        ->where('courier_id', $courierId)
                        ->lockForUpdate()
                        ->first();

                    if ($courierOrder && $fulfillmentMode === FulfillmentMode::DIRECT_COURIER->value) {
                        $this->courierBonusService->startSlaOnPickupReady($courierOrder);
                    }

                    DB::afterCommit(fn () => $this->orderStatusPushService->sendForTransition(
                        $sold->fresh(),
                        $previousStatus,
                        $fulfillmentMode === FulfillmentMode::DIRECT_COURIER->value ? 'B' : 'P'
                    ));
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Products collected. '.($allSellersDone ? 'Order fully sent to courier.' : 'Waiting for other shops...'),
                ], 200);
            });

            $sellerOrder = SellerOrder::where('order_id', $orderId)
                ->where('seller_id', $storeSellerId)
                ->first();
            if ($sellerOrder) {
                $this->orderRealtimeService->broadcastSellerOrderUpdated($sellerOrder, 'seller_order.handed_to_courier');
            }

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $this->qrTokenService->markPickupCodeUsed($parsedQr['normalized_qr']);
            }

            return $response;
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Xatolik: '.$th->getMessage(),
            ], 500);
        }
    }

    private function parsePickupQr(string $qr): ?array
    {
        $numeric = $this->qrTokenService->parsePickupCode($qr);
        if ($numeric) {
            return [
                'seller_id' => $numeric['seller_id'],
                'order_id' => $numeric['order_id'],
                'courier_id' => $numeric['courier_id'],
                'normalized_qr' => $numeric['code'],
            ];
        }

        $signed = $this->qrTokenService->parsePickupToken($qr);
        if ($signed) {
            return $signed + ['normalized_qr' => $qr];
        }

        if (! str_starts_with($qr, 'KC:')) {
            return null;
        }

        $parts = explode('|', str_replace('KC:', '', $qr));
        if (count($parts) !== 4) {
            return null;
        }

        $sellerId = (int) trim($parts[0]);
        $shopName = trim($parts[1]);
        $orderId = (int) trim($parts[2]);
        $courierId = (int) trim($parts[3]);

        if ($sellerId <= 0 || $orderId <= 0 || $courierId <= 0 || $shopName === '') {
            return null;
        }

        return [
            'seller_id' => $sellerId,
            'order_id' => $orderId,
            'courier_id' => $courierId,
            'normalized_qr' => $qr,
        ];
    }

    public function acceptOrder(Request $request, $id)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $this->hasOrderAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Orders available only for Owner, Admin, and Product Manager.',
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $sellerOrder = SellerOrder::where('id', $id)
            ->where('seller_id', $storeSellerId)
            ->first();

        if (! $sellerOrder || ! $this->staffCanAccessOrder($seller, $sellerOrder)) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $statusCode = $sellerOrder->status_code ?? SellerOrderStatusCode::fromLegacy($sellerOrder->status)->value;

        if ($statusCode === SellerOrderStatusCode::CANCELLED->value) {
            return response()->json(['success' => false, 'message' => 'Bekor qilingan buyurtmani qabul qilib bo‘lmaydi'], 422);
        }

        if ($statusCode === SellerOrderStatusCode::PAYMENT_PENDING->value) {
            return response()->json([
                'success' => false,
                'message' => "To'lov tasdiqlanmaguncha buyurtmani qabul qilib bo'lmaydi",
            ], 422);
        }

        if (in_array($statusCode, [
            SellerOrderStatusCode::ACCEPTED->value,
            SellerOrderStatusCode::HANDED_TO_COURIER->value,
        ], true)) {
            return response()->json([
                'success' => true,
                'message' => "Buyurtma allaqachon do'kon tomonidan qabul qilingan",
                'data' => ['status' => $statusCode, 'status_code' => $statusCode],
            ], 200);
        }

        $hasPendingItemCancellation = \App\Models\SellerOrderItem::query()
            ->where('order_id', $sellerOrder->id)
            ->where('refund_status', 'cancel_pending')
            ->exists();

        if ($hasPendingItemCancellation) {
            return response()->json([
                'success' => false,
                'message' => 'Mahsulot bo‘yicha 30 daqiqalik kutish holati bor. Avval uni sotuvda mavjud deb qaytaring yoki muddat tugashini kuting.',
            ], 422);
        }

        DB::transaction(function () use ($sellerOrder, $storeSellerId) {
            $sellerOrder->status = SellerOrderStatusCode::ACCEPTED->legacy();
            $sellerOrder->status_code = SellerOrderStatusCode::ACCEPTED->value;
            // Qabul qilingan vaqtni yozamiz — response_time hisoblash uchun.
            $sellerOrder->accepted_at = now();
            $sellerOrder->save();

            // Seller uchun o'rtacha javob vaqtini (soatda) yangilaymiz.
            // Faqat accepted_at bor buyurtmalar hisobga olinadi — eski
            // (null accepted_at) buyurtmalar statistikani buzmaydi.
            $avgHours = SellerOrder::where('seller_id', $storeSellerId)
                ->whereNotNull('accepted_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, accepted_at) / 3600) as avg_h')
                ->value('avg_h');

            if ($avgHours !== null) {
                // decimal(5,2) — 999.99 soatgacha, shuning uchun cap qo'yamiz.
                $capped = min(round((float) $avgHours, 2), 999.99);
                Seller::where('id', $storeSellerId)
                    ->update(['response_time_hours' => $capped]);
            }
        });

        $this->statusSync->updateSellerOrder($sellerOrder->fresh(), 2);
        $sellerOrder->refresh();
        $this->orderRealtimeService->broadcastSellerOrderUpdated($sellerOrder, 'seller_order.accepted');

        return response()->json([
            'success' => true,
            'message' => "Buyurtma do'kon tomonidan qabul qilindi",
            'data' => ['status' => SellerOrderStatusCode::ACCEPTED->value, 'status_code' => SellerOrderStatusCode::ACCEPTED->value],
        ], 200);
    }

    public function viewOrder(Request $request)
    {
        $orderId = $request->input('order_id');
        $seller = Auth::guard('seller')->user();

        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK
        if (! $this->hasOrderAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Orders available only for Owner, Admin, and Product Manager.',
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller); // ✅ OWNER ID

        // ✅ OWNER DO'KONI ORDERI
        $viewQuery = $this->applyVisibleSellerOrdersScope(
            Seller::find($storeSellerId)->orders()
        )
            ->where('id', $orderId)
            ->with([
                'seller.location',
                'order.fulfillment.hub:id,name,code',
                'items' => fn ($q) => $q->where('seller_id', $storeSellerId)
                    ->with(['book', 'stationery', 'variant', 'gift']),
            ])
            ->withCount(['items' => fn ($q) => $q->where('seller_id', $storeSellerId)]);
        $this->applyStaffBranchScope($viewQuery, $seller);
        $view = $viewQuery->first();

        if (! $view) {
            return response()->json([
                'success' => false,
                'message' => 'Order topilmadi',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatSellerOrder($view),
        ], 200);
    }

    public function ordersCount(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK
        if (! $this->hasOrderAccess($seller)) {
            return response()->json([
                'success' => true,
                'orders' => 0, // Bo'sh count
            ], 200);
        }

        $storeSellerId = $this->getStoreSellerId($seller); // ✅ OWNER ID

        // ✅ OWNER DO'KONI ORDER SONI
        $countQuery = $this->applyVisibleSellerOrdersScope(
            Seller::find($storeSellerId)->orders()
        );
        $this->applyStaffBranchScope($countQuery, $seller);
        $count = $countQuery->count();

        return response()->json([
            'success' => true,
            'orders' => $count,
        ], 200);
    }

    public function cancelItem(Request $request, int $itemId)
    {
        $request->validate([
            'reason_code' => ['required', 'string', 'max:64', Rule::in(SellerCancellationReasonCatalog::itemSelectableCodes())],
            'custom_note' => 'nullable|string|max:500',
        ]);

        if ($request->input('reason_code') === 'custom' && blank($request->input('custom_note'))) {
            return response()->json(['success' => false, 'message' => 'Custom sabab uchun izoh yozilishi shart.'], 422);
        }

        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $item = SellerOrder::query()
            ->select('seller_orders.seller_id')
            ->join('seller_order_items', 'seller_order_items.order_id', '=', 'seller_orders.id')
            ->where('seller_order_items.id', $itemId)
            ->exists();

        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Mahsulot topilmadi'], 404);
        }

        $sellerOrderItem = \App\Models\SellerOrderItem::query()->find($itemId);
        if (! $sellerOrderItem || ! $sellerOrderItem->order ||
            ! $this->staffCanAccessOrder($seller, $sellerOrderItem->order)) {
            return response()->json(['success' => false, 'message' => 'Bu mahsulot boshqa filial buyurtmasiga tegishli'], 403);
        }

        try {
            $result = $this->sellerOrderCancellationService->cancelItem(
                seller: $seller,
                item: $sellerOrderItem,
                reasonCode: (string) $request->input('reason_code'),
                customNote: $request->input('custom_note'),
            );

            $courierOrder = CourierOrder::query()
                ->where('order_id', $sellerOrderItem->order->order_id)
                ->first();
            if ($courierOrder) {
                $this->orderRealtimeService->broadcastCourierOrderUpdated(
                    $courierOrder,
                    'courier_order.item_unavailable',
                );
            }

            return response()->json(['success' => true, 'message' => $result['message'], 'data' => $result], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function restoreCancelledItem(Request $request, int $itemId)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $sellerOrderItem = \App\Models\SellerOrderItem::query()->find($itemId);
        if (! $sellerOrderItem) {
            return response()->json(['success' => false, 'message' => 'Mahsulot topilmadi'], 404);
        }
        if (! $sellerOrderItem->order ||
            ! $this->staffCanAccessOrder($seller, $sellerOrderItem->order)) {
            return response()->json(['success' => false, 'message' => 'Bu mahsulot boshqa filial buyurtmasiga tegishli'], 403);
        }

        try {
            $result = $this->sellerOrderCancellationService->restorePendingItemCancellation(
                seller: $seller,
                item: $sellerOrderItem,
            );

            $courierOrder = CourierOrder::query()
                ->where('order_id', $sellerOrderItem->order->order_id)
                ->first();
            if ($courierOrder) {
                $this->orderRealtimeService->broadcastCourierOrderUpdated(
                    $courierOrder,
                    'courier_order.item_restored',
                );
            }

            return response()->json(['success' => true, 'message' => $result['message'], 'data' => $result], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancelSellerOrder(Request $request, int $id)
    {
        $request->validate([
            'reason_code' => ['required', 'string', 'max:64', Rule::in(SellerCancellationReasonCatalog::orderSelectableCodes())],
            'custom_note' => 'nullable|string|max:500',
        ]);

        if ($request->input('reason_code') === 'custom' && blank($request->input('custom_note'))) {
            return response()->json(['success' => false, 'message' => 'Custom sabab uchun izoh yozilishi shart.'], 422);
        }

        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $sellerOrder = SellerOrder::query()->find($id);
        if (! $sellerOrder) {
            return response()->json(['success' => false, 'message' => 'Seller order topilmadi'], 404);
        }
        if (! $this->staffCanAccessOrder($seller, $sellerOrder)) {
            return response()->json(['success' => false, 'message' => 'Bu buyurtma boshqa filialga tegishli'], 403);
        }

        try {
            $result = $this->sellerOrderCancellationService->cancelSellerOrder(
                seller: $seller,
                sellerOrder: $sellerOrder,
                reasonCode: (string) $request->input('reason_code'),
                customNote: $request->input('custom_note'),
            );

            return response()->json(['success' => true, 'message' => $result['message'], 'data' => $result], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancelReasonCatalog()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'item_reasons' => SellerCancellationReasonCatalog::itemOptions(),
                'order_reasons' => SellerCancellationReasonCatalog::orderOptions(),
            ],
        ], 200);
    }
}
