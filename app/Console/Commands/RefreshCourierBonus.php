<?php

namespace App\Console\Commands;

use App\Models\CourierOrder;
use App\Services\CourierBonusService;
use App\Services\FCMService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3 — Kuryer bonus tizimini har minutda yangilab turuvchi planlovchi.
 *
 *   php artisan courier:refresh-bonus
 *
 * Vazifalari:
 *   1) Pending va biriktirilmagan buyurtmalar uchun pickup_bonus += surge_step
 *      (`CourierBonusService::tickPickupBonus`).
 *   2) Bonus chegarasidan o'tgan buyurtmalar uchun barcha tasdiqlangan
 *      kuryerlarga "Yuqori bonus!" push xabari (har buyurtma uchun bir marta).
 *   3) In_delivery statusdagi buyurtmalardan SLA tugashiga ≤5 min qolganlariga
 *      qabul qilgan kuryerga "5 daqiqa qoldi" push xabari (bir marta).
 *
 * Schedule: bootstrap/app.php → withSchedule() ichida `everyMinute()` bilan
 *   ro'yxatdan o'tkazilgan, withoutOverlapping() bilan birga.
 */
class RefreshCourierBonus extends Command
{
    protected $signature   = 'courier:refresh-bonus';
    protected $description = 'Kuryer surge bonusini oshirish + SLA ogohlantirish push lari';

    public function __construct(private readonly CourierBonusService $bonusService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            // 1) Pending orders — surge ni oshirish
            $tick = $this->bonusService->tickPickupBonus();

            if (!empty($tick['threshold_crossed'])) {
                $this->broadcastThresholdReached($tick['threshold_crossed']);
            }

            // 2) In-delivery orders — SLA-5min ogohlantirish
            $slaCandidates = $this->bonusService->findSlaWarningCandidates();
            foreach ($slaCandidates as $order) {
                $this->sendSlaWarning($order);
            }

            $this->info(sprintf(
                '[%s] surge ticked: %d, threshold pushed: %d, SLA warnings: %d',
                now()->format('H:i:s'),
                $tick['ticked'],
                count($tick['threshold_crossed']),
                count($slaCandidates),
            ));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('RefreshCourierBonus xatosi: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Xatolik: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Bonus chegarasidan o'tgan buyurtmalar uchun barcha kuryerlarga push.
     * Default tilda yuboramiz (uz) — har bir kuryerning lang sozlamasi yo'q,
     * shuning uchun yagona xabar yetarli (kelajakda kengaytirsa bo'ladi).
     *
     * @param  CourierOrder[]  $orders
     */
    private function broadcastThresholdReached(array $orders): void
    {
        try {
            $tokens = $this->collectActiveCourierTokens();
            if (empty($tokens)) {
                return;
            }

            $fcm = new FCMService('courier');

            foreach ($orders as $order) {
                $bonus = (int) $order->pickup_bonus;
                $title = __('courier_api.bonus_threshold_push_title');
                $body  = __('courier_api.bonus_threshold_push_body', [
                    'amount' => number_format($bonus, 0, '.', ' '),
                ]);

                $fcm->send($tokens, $title, $body, [
                    'type'         => 'bonus_threshold',
                    'order_id'     => (string) $order->id,
                    'pickup_bonus' => (string) $bonus,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('broadcastThresholdReached xatosi: ' . $e->getMessage());
        }
    }

    /**
     * Bitta kuryerga (qabul qilgan kuryer) SLA-5min ogohlantirish.
     */
    private function sendSlaWarning(CourierOrder $order): void
    {
        try {
            if (!$order->courier_id) {
                return;
            }

            $tokens = DB::table('connected_devices')
                ->where('user_type', 'courier')
                ->where('user_id', $order->courier_id)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->all();

            if (!empty($tokens)) {
                $title = __('courier_api.sla_warning_push_title');
                $body  = __('courier_api.sla_warning_push_body', ['id' => $order->order_id ?? $order->id]);

                (new FCMService('courier'))->send($tokens, $title, $body, [
                    'type'         => 'sla_warning',
                    'order_id'     => (string) $order->id,
                    'sla_deadline' => optional($order->sla_deadline)->toIso8601String() ?? '',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);
            }

            // Push muvaffaqiyatli yoki yo'q — flagni yoqamiz, takrorlanmasin.
            $this->bonusService->markSlaWarningNotified($order);
        } catch (\Throwable $e) {
            Log::error('sendSlaWarning xatosi: ' . $e->getMessage());
        }
    }

    /**
     * Tasdiqlangan kuryerlarning barcha aktiv FCM tokenlari.
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
