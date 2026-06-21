<?php

namespace App\Services;

use App\Enums\CourierOrderStatusCode;
use App\Enums\CourierTaskStatusCode;
use App\Models\CourierOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Real-time kuryer push xabarlari uchun yordamchi service.
 *
 * Vazifasi: yangi `pending` holatdagi `CourierOrder` paydo bo'lganda, avval
 * bo'sh online kuryerlarni topib FCM data-message yuborish. Flutter ilovasi bu
 * xabarni qabul qilib, mavjud buyurtmalar ro'yxatini yangilaydi.
 *
 * Foydalanish:
 *   app(\App\Services\CourierBroadcaster::class)->notifyNewOrderAvailable($order);
 */
class CourierBroadcaster
{
    public function __construct(
        private readonly FCMService $fcmService
    ) {
    }

    /**
     * Yangi buyurtma e'lon qilingani haqida barcha tasdiqlangan kuryerlarni xabardor qilish.
     */
    public function notifyNewOrderAvailable(CourierOrder $courierOrder): array
    {
        try {
            $target = $this->collectSmartCourierTokens($courierOrder);
            $tokens = $target['tokens'];

            if (empty($tokens)) {
                Log::info('CourierBroadcaster skipped', [
                    'order_id' => $courierOrder->order_id,
                    'reason' => $target['reason'] ?? 'no_tokens',
                ]);

                return [
                    'skipped' => true,
                    'reason' => $target['reason'] ?? 'no_tokens',
                    'target_wave' => $target['wave'] ?? null,
                ];
            }

            $title = __('courier_api.order_confirmed') === 'courier_api.order_confirmed'
                ? "Yangi buyurtma!"
                : "Yangi buyurtma!"; // Avval kuryer notifications uchun shu nom yetarli

            $body = "Buyurtma #{$courierOrder->id} kutmoqda. Tezroq qabul qiling!";

            $data = [
                'type'         => 'new_order',
                'order_id'     => (string) $courierOrder->id,
                'sold_id'      => (string) $courierOrder->order_id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'amount'       => (string) ($courierOrder->amount ?? 0),
                'courierPrice' => (string) ($courierOrder->courierPrice ?? 0),
                'target_wave'  => (string) ($target['wave'] ?? 'idle'),
            ];

            // FCM courier loyihasidan yuboramiz, chunki kuryer ilovasida `courier`
            // Firebase project ishlatiladi.
            $broadcaster = new FCMService('courier');
            $result = $broadcaster->send($tokens, $title, $body, $data);

            Log::info('CourierBroadcaster sent', [
                'order_id' => $courierOrder->order_id,
                'courier_order_id' => $courierOrder->id,
                'wave' => $target['wave'] ?? 'idle',
                'couriers' => $target['courier_ids'] ?? [],
                'tokens' => count($tokens),
                'result' => $result,
            ]);

            return $result + [
                'target_wave' => $target['wave'] ?? 'idle',
                'target_couriers' => $target['courier_ids'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('CourierBroadcaster error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Push strategiyasi:
     * 1. Avval online, approved va umuman faol order/task olib ketmayotgan
     *    kuryerlar.
     * 2. Agar bo'sh kuryer topilmasa, active order limiti to'lmagan online
     *    kuryerlar.
     *
     * Shunda push keraksiz hammaga ketmaydi, lekin order ham jim qolib ketmaydi.
     */
    private function collectSmartCourierTokens(CourierOrder $courierOrder): array
    {
        $idle = $this->courierTokenRows($courierOrder, true, 40);
        if ($idle->isNotEmpty()) {
            return $this->tokenResult($idle, 'idle');
        }

        $withCapacity = $this->courierTokenRows($courierOrder, false, 24);
        if ($withCapacity->isNotEmpty()) {
            return $this->tokenResult($withCapacity, 'capacity');
        }

        return ['tokens' => [], 'courier_ids' => [], 'wave' => 'none', 'reason' => 'no_available_couriers'];
    }

    private function courierTokenRows(CourierOrder $courierOrder, bool $idleOnly, int $limit)
    {
        $hasOnlineColumn = Schema::hasColumn('couriers', 'is_online');
        $hasLocationColumns = Schema::hasColumn('couriers', 'current_lat')
            && Schema::hasColumn('couriers', 'current_lon');
        $lastSeenExpression = $this->lastSeenExpression();

        $activeOrders = DB::table('courier_orders')
            ->select('courier_id', DB::raw('COUNT(*) as active_orders_count'))
            ->whereNotNull('courier_id')
            ->where(function ($query) {
                $query->whereIn('status_code', [
                    CourierOrderStatusCode::IN_DELIVERY->value,
                ])->orWhereIn('status', [
                    CourierOrderStatusCode::IN_DELIVERY->legacy(),
                ]);
            })
            ->groupBy('courier_id');

        $activeTasks = DB::table('courier_tasks')
            ->select('courier_id', DB::raw('COUNT(*) as active_tasks_count'))
            ->whereNotNull('courier_id')
            ->whereIn('status_code', [
                CourierTaskStatusCode::ACCEPTED->value,
                CourierTaskStatusCode::ARRIVED_AT_PICKUP->value,
                CourierTaskStatusCode::PICKED_UP->value,
            ])
            ->groupBy('courier_id');

        $query = DB::table('connected_devices')
            ->join('couriers', function ($join) {
                $join->on('couriers.id', '=', 'connected_devices.user_id')
                    ->where('connected_devices.user_type', '=', 'courier');
            })
            ->leftJoinSub($activeOrders, 'active_orders', function ($join) {
                $join->on('active_orders.courier_id', '=', 'couriers.id');
            })
            ->leftJoinSub($activeTasks, 'active_tasks', function ($join) {
                $join->on('active_tasks.courier_id', '=', 'couriers.id');
            })
            ->where('couriers.status', 'approved')
            ->when($hasOnlineColumn, fn ($builder) => $builder->where('couriers.is_online', true))
            ->whereNotNull('connected_devices.fcm_token')
            ->where('connected_devices.fcm_token', '!=', '')
            ->when($idleOnly, function ($builder) {
                $builder
                    ->whereRaw('COALESCE(active_orders.active_orders_count, 0) = 0')
                    ->whereRaw('COALESCE(active_tasks.active_tasks_count, 0) = 0');
            }, function ($builder) {
                $builder->whereRaw('COALESCE(active_orders.active_orders_count, 0) < 3');
            })
            ->select([
                'couriers.id as courier_id',
                'connected_devices.fcm_token',
                DB::raw('COALESCE(active_orders.active_orders_count, 0) as active_orders_count'),
                DB::raw('COALESCE(active_tasks.active_tasks_count, 0) as active_tasks_count'),
            ])
            ->orderBy('active_orders_count')
            ->orderBy('active_tasks_count');

        if ($hasLocationColumns) {
            $this->orderByDistanceIfPossible($query, $courierOrder);
        }

        $query->orderByDesc(DB::raw($lastSeenExpression));

        return $query->limit($limit)->get();
    }

    private function lastSeenExpression(): string
    {
        $columns = [];

        foreach (['location_updated_at', 'availability_updated_at', 'updated_at'] as $column) {
            if (Schema::hasColumn('couriers', $column)) {
                $columns[] = "couriers.{$column}";
            }
        }

        return $columns === []
            ? 'connected_devices.updated_at'
            : 'COALESCE('.implode(', ', $columns).', connected_devices.updated_at)';
    }

    private function orderByDistanceIfPossible($query, CourierOrder $courierOrder): void
    {
        $payload = $courierOrder->task_pickup_address;
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }
        if (! is_array($payload)) {
            return;
        }

        $lat = $payload['lat'] ?? $payload['latitude'] ?? null;
        $lon = $payload['lon'] ?? $payload['lng'] ?? $payload['longitude'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lon)) {
            return;
        }

        $query
            ->orderByRaw('CASE WHEN couriers.current_lat IS NULL OR couriers.current_lon IS NULL THEN 1 ELSE 0 END asc')
            ->orderByRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(couriers.current_lat)) * cos(radians(couriers.current_lon) - radians(?)) + sin(radians(?)) * sin(radians(couriers.current_lat)))) asc',
                [(float) $lat, (float) $lon, (float) $lat]
            );
    }

    private function tokenResult($rows, string $wave): array
    {
        return [
            'tokens' => $rows->pluck('fcm_token')->filter()->unique()->values()->all(),
            'courier_ids' => $rows->pluck('courier_id')->unique()->values()->all(),
            'wave' => $wave,
        ];
    }
}
