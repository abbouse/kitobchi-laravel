<?php

namespace App\Services;

use App\Models\CourierOrder;
use App\Models\Couriers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Real-time kuryer push xabarlari uchun yordamchi service.
 *
 * Vazifasi: yangi `pending` holatdagi `CourierOrder` paydo bo'lganda barcha
 * tasdiqlangan kuryerlarning telefoniga FCM data-message yuborish. Flutter
 * ilovasi bu xabarni qabul qilib, mavjud buyurtmalar ro'yxatini yangilaydi.
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
            $tokens = $this->collectActiveCourierTokens();

            if (empty($tokens)) {
                return ['skipped' => true, 'reason' => 'no_tokens'];
            }

            $title = __('courier_api.order_confirmed') === 'courier_api.order_confirmed'
                ? "Yangi buyurtma!"
                : "Yangi buyurtma!"; // Avval kuryer notifications uchun shu nom yetarli

            $body = "Buyurtma #{$courierOrder->id} kutmoqda. Tezroq qabul qiling!";

            $data = [
                'type'         => 'new_order',
                'order_id'     => (string) $courierOrder->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'amount'       => (string) ($courierOrder->amount ?? 0),
                'courierPrice' => (string) ($courierOrder->courierPrice ?? 0),
            ];

            // FCM courier loyihasidan yuboramiz, chunki kuryer ilovasida `courier`
            // Firebase project ishlatiladi.
            $broadcaster = new FCMService('courier');
            return $broadcaster->send($tokens, $title, $body, $data);
        } catch (\Throwable $e) {
            Log::error('CourierBroadcaster error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Connected_devices jadvalidan tasdiqlangan kuryerlarning faol FCM tokenlarini olish.
     * `couriers.status = 'approved'` filtri qo'llaniladi.
     */
    private function collectActiveCourierTokens(): array
    {
        return DB::table('connected_devices')
            ->join('couriers', function ($join) {
                $join->on('couriers.id', '=', 'connected_devices.user_id')
                     ->where('connected_devices.user_type', '=', 'courier');
            })
            ->where('couriers.status', 'approved')
            ->whereNotNull('connected_devices.fcm_token')
            ->where('connected_devices.fcm_token', '!=', '')
            ->pluck('connected_devices.fcm_token')
            ->unique()
            ->values()
            ->all();
    }
}
