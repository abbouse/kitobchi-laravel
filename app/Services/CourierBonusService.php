<?php

namespace App\Services;

use App\Models\CourierOrder;
use App\Models\ProjectSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * CourierBonusService — Phase 3 ikki bosqichli kuryer bonus tizimi.
 *
 * 1) Pre-acceptance SURGE
 *    Buyurtma yaratilganda pickup_bonus = 0. Har minutda planlovchi
 *    `tickPickupBonus()` ni chaqiradi: pending statusdagi va kuryer biriktirilmagan
 *    barcha buyurtmalar uchun pickup_bonus += surge_step (max surge_max gacha).
 *
 * 2) Post-acceptance PENALTY
 *    Kuryer qabul qilganda `lockBonusOnAccept()` chaqiriladi: hozirgi
 *    pickup_bonus snapshotga olinadi (locked_bonus), picked_up_at = now,
 *    sla_deadline = now + sla_minutes.
 *
 *    Yetkazib berilganda `computeFinalBonus()` chaqiriladi: agar
 *    delivered_at <= sla_deadline → final_bonus = locked_bonus (penalty yo'q).
 *    Aks holda final_bonus = max(0, locked_bonus - kechikgan_minut * penalty_step).
 *
 * 3) CUSTOMER DELAY
 *    Kuryer "Mijoz javob bermayapti" tugmasini bosgani — SLA ni pause qiladi.
 *    Resume bo'lganda yig'ilgan delay total_delay_seconds ga qo'shiladi va
 *    sla_deadline shu qadar oldinga suriladi.
 *
 * Sozlamalar `project_settings` jadvalining yagona qatoridan olinadi.
 * Default qiymatlar (sozlama yo'q bo'lsa) — surge_step=500, surge_max=10000,
 * surge_threshold=5000, sla_minutes=45, penalty_step=300.
 */
class CourierBonusService
{
    private const DEFAULT_SURGE_STEP      = 500;
    private const DEFAULT_SURGE_MAX       = 10000;
    private const DEFAULT_SURGE_THRESHOLD = 5000;
    private const DEFAULT_SLA_MINUTES     = 45;
    private const DEFAULT_PENALTY_STEP    = 300;

    private ?ProjectSetting $cachedSettings = null;

    /** Sozlamalar — har request uchun bir marta o'qiymiz. */
    public function settings(): array
    {
        if ($this->cachedSettings === null) {
            $this->cachedSettings = ProjectSetting::query()->first();
        }
        $s = $this->cachedSettings;

        return [
            'surge_step'      => (int) ($s->courier_surge_step      ?? self::DEFAULT_SURGE_STEP),
            'surge_max'       => (int) ($s->courier_surge_max       ?? self::DEFAULT_SURGE_MAX),
            'surge_threshold' => (int) ($s->courier_surge_threshold ?? self::DEFAULT_SURGE_THRESHOLD),
            'sla_minutes'     => (int) ($s->courier_sla_minutes     ?? self::DEFAULT_SLA_MINUTES),
            'penalty_step'    => (int) ($s->courier_penalty_step    ?? self::DEFAULT_PENALTY_STEP),
        ];
    }

    // =========================================================================
    //  1. SURGE TICK — har minut chaqiriladi
    // =========================================================================

    /**
     * Pending va biriktirilmagan barcha buyurtmalar uchun pickup_bonus ni
     * surge_step ga oshiradi (max surge_max gacha).
     *
     * @return array{ticked:int, threshold_crossed:CourierOrder[]}
     *    `threshold_crossed` — chegaradan endi o'tgan buyurtmalar (push uchun).
     */
    public function tickPickupBonus(): array
    {
        $cfg = $this->settings();
        $thresholdCrossed = [];
        $ticked = 0;

        // Pending va kuryer biriktirilmagan, max ga yetmagan buyurtmalarni olamiz.
        $orders = CourierOrder::query()
            ->where('status', 'pending')
            ->whereNull('courier_id')
            ->where('pickup_bonus', '<', $cfg['surge_max'])
            ->get();

        foreach ($orders as $order) {
            $oldBonus = (int) $order->pickup_bonus;
            $newBonus = min($oldBonus + $cfg['surge_step'], $cfg['surge_max']);

            if ($newBonus === $oldBonus) {
                continue;
            }

            $order->pickup_bonus = $newBonus;

            // Threshold o'tdi (oldin past, endi yuqori) — push flag tekshirish.
            $crossedNow = ($oldBonus < $cfg['surge_threshold'])
                && ($newBonus >= $cfg['surge_threshold'])
                && !$order->bonus_threshold_notified;

            if ($crossedNow) {
                $order->bonus_threshold_notified = true;
                $thresholdCrossed[] = $order;
            }

            $order->save();
            $ticked++;
        }

        return [
            'ticked'            => $ticked,
            'threshold_crossed' => $thresholdCrossed,
        ];
    }

    // =========================================================================
    //  2. ACCEPTANCE — bonusni qulflash
    // =========================================================================

    /**
     * Kuryer buyurtmani qabul qilganda chaqiriladi (CourierOrderController@confirmOrder).
     * locked_bonus, picked_up_at, sla_deadline ni o'rnatadi.
     */
    public function lockBonusOnAccept(CourierOrder $order): void
    {
        $cfg = $this->settings();
        $now = Carbon::now();

        $order->locked_bonus = (int) $order->pickup_bonus;
        $order->picked_up_at = $now;
        $order->sla_deadline = $now->copy()->addMinutes($cfg['sla_minutes']);
        // bonus_threshold_notified ni qayta yoqib qo'yamiz, chunki post-acceptance
        // bosqichida yangi xabarlarni alohida flaglar boshqaradi.
        $order->save();
    }

    // =========================================================================
    //  3. DELIVERY — penalty hisoblab final_bonus ni yozish
    // =========================================================================

    /**
     * Kuryer mijozga yetkazganda chaqiriladi (CourierOrderController@toCustomer).
     * SLA dan kechikkan minutlarga ko'paytirib penalty hisoblaydi va
     * final_bonus = max(0, locked_bonus - penalty) ni yozadi.
     *
     * @return int  — yakuniy bonus (courierPrice ustiga qo'shiladigan summa)
     */
    public function computeFinalBonus(CourierOrder $order): int
    {
        $cfg = $this->settings();

        $locked = (int) ($order->locked_bonus ?? 0);

        if (!$order->sla_deadline) {
            // Eski yozuv (locked_bonus o'rnatilmagan) — bonus 0 deb hisoblaymiz.
            $order->final_bonus = $locked;
            $order->save();
            return $locked;
        }

        $now      = Carbon::now();
        $deadline = Carbon::parse($order->sla_deadline);

        // Agar customer_delay flag hali yoniq bo'lsa, oxirgi delay oraliqini
        // hisoblab total_delay_seconds ga qo'shamiz (ya'ni delivery payti pause
        // tugagan deb qabul qilamiz).
        if ($order->is_customer_delay && $order->customer_delay_started_at) {
            $delayElapsed = $now->diffInSeconds(Carbon::parse($order->customer_delay_started_at));
            $order->total_delay_seconds = (int) $order->total_delay_seconds + $delayElapsed;
            $order->is_customer_delay = false;
            $order->customer_delay_started_at = null;
            // sla_deadline ni delay qadar siljitamiz.
            $deadline = $deadline->copy()->addSeconds($delayElapsed);
            $order->sla_deadline = $deadline;
        }

        if ($now->lessThanOrEqualTo($deadline)) {
            // SLA ichida yetkazildi — penalty yo'q.
            $final = $locked;
        } else {
            $minutesLate = (int) ceil($now->diffInSeconds($deadline) / 60);
            $penalty     = $minutesLate * $cfg['penalty_step'];
            $final       = max(0, $locked - $penalty);
        }

        $order->final_bonus = $final;
        // courierBonus ustunini ham yangilab ketamiz — eski kod (admin panel,
        // hisobotlar) bu ustundan o'qishi mumkin.
        $order->courierBonus = $final;
        $order->save();

        return $final;
    }

    // =========================================================================
    //  4. CUSTOMER DELAY — pause/resume
    // =========================================================================

    /**
     * Kuryer "mijoz javob bermayapti" tugmasini bosganda chaqiriladi.
     * Agar oldin pause qilingan bo'lmasa — pause boshlanadi.
     * Agar pause yoniq bo'lsa — resume qilamiz va o'tgan vaqtni hisoblaymiz.
     *
     * @return array{paused:bool, total_delay_seconds:int, sla_deadline:?string}
     */
    public function toggleCustomerDelay(CourierOrder $order): array
    {
        $now = Carbon::now();

        if (!$order->is_customer_delay) {
            // PAUSE
            $order->is_customer_delay = true;
            $order->customer_delay_started_at = $now;
            $order->save();

            return [
                'paused'              => true,
                'total_delay_seconds' => (int) $order->total_delay_seconds,
                'sla_deadline'        => optional($order->sla_deadline)->toIso8601String(),
            ];
        }

        // RESUME
        $delayElapsed = 0;
        if ($order->customer_delay_started_at) {
            $delayElapsed = $now->diffInSeconds(Carbon::parse($order->customer_delay_started_at));
        }

        $order->total_delay_seconds = (int) $order->total_delay_seconds + $delayElapsed;
        $order->is_customer_delay = false;
        $order->customer_delay_started_at = null;

        // sla_deadline ni delay qadar oldinga suramiz, shunda kuryer kechikmagan
        // hisoblanadi (mijoz javob bermay turgan vaqt jarima yo'q).
        if ($order->sla_deadline && $delayElapsed > 0) {
            $order->sla_deadline = Carbon::parse($order->sla_deadline)->addSeconds($delayElapsed);
        }

        $order->save();

        return [
            'paused'              => false,
            'total_delay_seconds' => (int) $order->total_delay_seconds,
            'sla_deadline'        => optional($order->sla_deadline)->toIso8601String(),
        ];
    }

    // =========================================================================
    //  5. SLA WARNING — har minut chaqiriladi (in_delivery uchun)
    // =========================================================================

    /**
     * SLA tugashiga 5 minut yoki kamroq qolgan in_delivery buyurtmalarni
     * topadi va `sla_warning_notified=false` bo'lganlarini qaytaradi
     * (chaqiruvchi push yuborib flagni true qiladi).
     *
     * @return CourierOrder[]
     */
    public function findSlaWarningCandidates(): array
    {
        $now      = Carbon::now();
        $cutoff   = $now->copy()->addMinutes(5);

        $orders = CourierOrder::query()
            ->where('status', 'in_delivery')
            ->whereNotNull('sla_deadline')
            ->where('sla_warning_notified', false)
            ->where('is_customer_delay', false)
            ->whereBetween('sla_deadline', [$now, $cutoff])
            ->get();

        return $orders->all();
    }

    /**
     * Push yuborilgandan keyin chaqiramiz — flag yoqamiz.
     */
    public function markSlaWarningNotified(CourierOrder $order): void
    {
        $order->sla_warning_notified = true;
        $order->save();
    }
}
