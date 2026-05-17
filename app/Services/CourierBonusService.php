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
 *    pickup_bonus snapshotga olinadi (locked_bonus).
 *
 *    Muhim: marketplace oqimida SLA kuryer "buyurtmani oldi" degan
 *    confirmation paytidan emas, barcha do'konlar buyurtmani kuryerga berib
 *    bo'lgach boshlanishi kerak. Shu sabab picked_up_at / sla_deadline
 *    `startSlaOnPickupReady()` da o'rnatiladi.
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
    public const MAX_CUSTOMER_DELAY_COUNT = 3;
    public const MAX_CUSTOMER_DELAY_SECONDS = 900;
    public const MAX_SINGLE_CUSTOMER_DELAY_SECONDS = 300;
    public const MAX_SURGE_STEP = 5000;
    public const MAX_SURGE_MAX = 50000;
    public const MAX_PENALTY_STEP = 10000;
    public const MAX_SLA_MINUTES = 180;

    private const DEFAULT_SURGE_STEP      = 500;
    private const DEFAULT_SURGE_MAX       = 10000;
    private const DEFAULT_SURGE_THRESHOLD = 5000;
    private const DEFAULT_SLA_MINUTES     = 45;
    private const DEFAULT_PENALTY_STEP    = 300;

    private ?ProjectSetting $cachedSettings = null;

    private function persistOrderState(CourierOrder $order, array $attributes): void
    {
        CourierOrder::query()
            ->whereKey($order->id)
            ->update(array_merge($attributes, [
                'updated_at' => now(),
            ]));

        foreach ($attributes as $key => $value) {
            $order->setAttribute($key, $value);
        }

        $order->syncOriginal();
    }

    /** Sozlamalar — har request uchun bir marta o'qiymiz. */
    public function settings(): array
    {
        if ($this->cachedSettings === null) {
            $this->cachedSettings = ProjectSetting::query()->first();
        }
        $s = $this->cachedSettings;

        $surgeStep = max(0, min((int) ($s->courier_surge_step ?? self::DEFAULT_SURGE_STEP), self::MAX_SURGE_STEP));
        $surgeMax = max(0, min((int) ($s->courier_surge_max ?? self::DEFAULT_SURGE_MAX), self::MAX_SURGE_MAX));
        $surgeThreshold = max(0, min((int) ($s->courier_surge_threshold ?? self::DEFAULT_SURGE_THRESHOLD), $surgeMax));
        $slaMinutes = max(1, min((int) ($s->courier_sla_minutes ?? self::DEFAULT_SLA_MINUTES), self::MAX_SLA_MINUTES));
        $penaltyStep = max(0, min((int) ($s->courier_penalty_step ?? self::DEFAULT_PENALTY_STEP), self::MAX_PENALTY_STEP));

        return [
            'surge_step'      => $surgeStep,
            'surge_max'       => $surgeMax,
            'surge_threshold' => $surgeThreshold,
            'sla_minutes'     => $slaMinutes,
            'penalty_step'    => $penaltyStep,
        ];
    }

    public function normalizeBonusState(CourierOrder $order, bool $persist = true): CourierOrder
    {
        $cfg = $this->settings();

        $pickup = max(0, min((int) ($order->pickup_bonus ?? 0), $cfg['surge_max']));
        $locked = $order->locked_bonus === null
            ? null
            : max(0, min((int) $order->locked_bonus, $cfg['surge_max']));
        $final = $order->final_bonus === null
            ? null
            : max(0, min((int) $order->final_bonus, $cfg['surge_max']));

        if ($order->status === 'pending' && !$order->courier_id) {
            $displayBonus = $pickup;
        } elseif ($order->status === 'in_delivery') {
            $displayBonus = $locked ?? $pickup;
        } else {
            $displayBonus = $final ?? $locked ?? 0;
        }

        $order->pickup_bonus = $pickup;
        $order->locked_bonus = $locked;
        $order->final_bonus = $final;
        $order->courierBonus = max(0, min((int) $displayBonus, $cfg['surge_max']));

        if ($persist && $order->isDirty(['pickup_bonus', 'locked_bonus', 'final_bonus', 'courierBonus'])) {
            $this->persistOrderState($order, [
                'pickup_bonus' => $order->pickup_bonus,
                'locked_bonus' => $order->locked_bonus,
                'final_bonus' => $order->final_bonus,
                'courierBonus' => $order->courierBonus,
            ]);
        }

        return $order;
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
            $this->normalizeBonusState($order, false);
            $oldBonus = (int) $order->pickup_bonus;
            $newBonus = min($oldBonus + $cfg['surge_step'], $cfg['surge_max']);

            if ($newBonus === $oldBonus) {
                continue;
            }

            $order->pickup_bonus = $newBonus;
            $order->courierBonus = $newBonus;

            // Threshold o'tdi (oldin past, endi yuqori) — push flag tekshirish.
            $crossedNow = ($oldBonus < $cfg['surge_threshold'])
                && ($newBonus >= $cfg['surge_threshold'])
                && !$order->bonus_threshold_notified;

            if ($crossedNow) {
                $order->bonus_threshold_notified = true;
                $thresholdCrossed[] = $order;
            }

            $this->persistOrderState($order, [
                'pickup_bonus' => $order->pickup_bonus,
                'courierBonus' => $order->courierBonus,
                'bonus_threshold_notified' => $order->bonus_threshold_notified,
            ]);
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
     * Bu bosqichda faqat bonusni "lock" qilamiz. SLA hali boshlanmaydi.
     */
    public function lockBonusOnAccept(CourierOrder $order): void
    {
        $this->normalizeBonusState($order, false);
        $order->locked_bonus = (int) $order->pickup_bonus;
        $order->courierBonus = (int) $order->locked_bonus;
        $order->final_bonus = null;
        $this->persistOrderState($order, [
            'locked_bonus' => $order->locked_bonus,
            'courierBonus' => $order->courierBonus,
            'final_bonus' => $order->final_bonus,
        ]);
    }

    /**
     * Barcha do'konlar kuryerga topshirib bo'lgach SLA ni boshlaydi.
     * Idempotent: bir marta boshlangan bo'lsa qayta yozmaydi.
     */
    public function startSlaOnPickupReady(CourierOrder $order): void
    {
        $this->normalizeBonusState($order, false);
        if ($order->picked_up_at && $order->sla_deadline) {
            return;
        }

        $cfg = $this->settings();
        $now = Carbon::now();

        if (!$order->locked_bonus) {
            $order->locked_bonus = (int) $order->pickup_bonus;
        }

        $order->courierBonus = (int) $order->locked_bonus;

        $order->picked_up_at = $order->picked_up_at ?: $now;
        $order->sla_deadline = $order->sla_deadline ?: $now->copy()->addMinutes($cfg['sla_minutes']);
        $order->sla_warning_notified = false;
        $this->persistOrderState($order, [
            'locked_bonus' => $order->locked_bonus,
            'courierBonus' => $order->courierBonus,
            'picked_up_at' => $order->picked_up_at,
            'sla_deadline' => $order->sla_deadline,
            'sla_warning_notified' => $order->sla_warning_notified,
        ]);
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
        $this->normalizeBonusState($order, false);

        $locked = (int) ($order->locked_bonus ?? 0);

        if (!$order->sla_deadline) {
            // SLA hali boshlanmagan yoki eski yozuv — lock qilingan bonusni saqlab qolamiz.
            $order->final_bonus = $locked;
            $order->courierBonus = $locked;
            $this->persistOrderState($order, [
                'final_bonus' => $order->final_bonus,
                'courierBonus' => $order->courierBonus,
            ]);
            return $locked;
        }

        $now      = Carbon::now();
        $deadline = Carbon::parse($order->sla_deadline);

        // Agar customer_delay flag hali yoniq bo'lsa, oxirgi delay oraliqini
        // hisoblab total_delay_seconds ga qo'shamiz (ya'ni delivery payti pause
        // tugagan deb qabul qilamiz).
        if ($order->is_customer_delay && $order->customer_delay_started_at) {
            $startedAt = Carbon::parse($order->customer_delay_started_at);
            $delayElapsed = $startedAt->greaterThan($now)
                ? 0
                : $startedAt->diffInSeconds($now);
            $remainingBudget = max(0, self::MAX_CUSTOMER_DELAY_SECONDS - (int) $order->total_delay_seconds);
            $delayElapsed = min($delayElapsed, self::MAX_SINGLE_CUSTOMER_DELAY_SECONDS, $remainingBudget);
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
        $this->persistOrderState($order, [
            'total_delay_seconds' => $order->total_delay_seconds,
            'is_customer_delay' => $order->is_customer_delay,
            'customer_delay_started_at' => $order->customer_delay_started_at,
            'sla_deadline' => $order->sla_deadline,
            'final_bonus' => $order->final_bonus,
            'courierBonus' => $order->courierBonus,
        ]);

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

        if (!$order->picked_up_at || !$order->sla_deadline) {
            return [
                'success'             => false,
                'paused'              => false,
                'total_delay_seconds' => (int) $order->total_delay_seconds,
                'customer_delay_count'=> (int) $order->customer_delay_count,
                'remaining_delay_seconds' => max(0, self::MAX_CUSTOMER_DELAY_SECONDS - (int) $order->total_delay_seconds),
                'message'             => __('courier_api.customer_delay_invalid'),
                'sla_deadline'        => optional($order->sla_deadline)->toIso8601String(),
            ];
        }

        if (!$order->is_customer_delay) {
            if ((int) $order->customer_delay_count >= self::MAX_CUSTOMER_DELAY_COUNT) {
                return [
                    'success'             => false,
                    'paused'              => false,
                    'total_delay_seconds' => (int) $order->total_delay_seconds,
                    'customer_delay_count'=> (int) $order->customer_delay_count,
                    'remaining_delay_seconds' => max(0, self::MAX_CUSTOMER_DELAY_SECONDS - (int) $order->total_delay_seconds),
                    'message'             => __('courier_api.customer_delay_limit_reached'),
                    'sla_deadline'        => optional($order->sla_deadline)->toIso8601String(),
                ];
            }

            if ((int) $order->total_delay_seconds >= self::MAX_CUSTOMER_DELAY_SECONDS) {
                return [
                    'success'             => false,
                    'paused'              => false,
                    'total_delay_seconds' => (int) $order->total_delay_seconds,
                    'customer_delay_count'=> (int) $order->customer_delay_count,
                    'remaining_delay_seconds' => 0,
                    'message'             => __('courier_api.customer_delay_limit_reached'),
                    'sla_deadline'        => optional($order->sla_deadline)->toIso8601String(),
                ];
            }

            // PAUSE
            $order->is_customer_delay = true;
            $order->customer_delay_started_at = $now;
            $order->customer_delay_count = (int) $order->customer_delay_count + 1;
            $this->persistOrderState($order, [
                'is_customer_delay' => $order->is_customer_delay,
                'customer_delay_started_at' => $order->customer_delay_started_at,
                'customer_delay_count' => $order->customer_delay_count,
            ]);

            return [
                'success'             => true,
                'paused'              => true,
                'total_delay_seconds' => (int) $order->total_delay_seconds,
                'customer_delay_count'=> (int) $order->customer_delay_count,
                'remaining_delay_seconds' => max(0, self::MAX_CUSTOMER_DELAY_SECONDS - (int) $order->total_delay_seconds),
                'sla_deadline'        => optional($order->sla_deadline)->toIso8601String(),
            ];
        }

        // RESUME
        $delayElapsed = 0;
        if ($order->customer_delay_started_at) {
            $startedAt = Carbon::parse($order->customer_delay_started_at);
            $delayElapsed = $startedAt->greaterThan($now)
                ? 0
                : $startedAt->diffInSeconds($now);
        }

        $remainingBudget = max(0, self::MAX_CUSTOMER_DELAY_SECONDS - (int) $order->total_delay_seconds);
        $delayElapsed = min($delayElapsed, self::MAX_SINGLE_CUSTOMER_DELAY_SECONDS, $remainingBudget);

        $order->total_delay_seconds = (int) $order->total_delay_seconds + $delayElapsed;
        $order->is_customer_delay = false;
        $order->customer_delay_started_at = null;

        // sla_deadline ni delay qadar oldinga suramiz, shunda kuryer kechikmagan
        // hisoblanadi (mijoz javob bermay turgan vaqt jarima yo'q).
        if ($order->sla_deadline && $delayElapsed > 0) {
            $order->sla_deadline = Carbon::parse($order->sla_deadline)->addSeconds($delayElapsed);
        }

        $this->persistOrderState($order, [
            'total_delay_seconds' => $order->total_delay_seconds,
            'is_customer_delay' => $order->is_customer_delay,
            'customer_delay_started_at' => $order->customer_delay_started_at,
            'sla_deadline' => $order->sla_deadline,
        ]);

        return [
            'success'             => true,
            'paused'              => false,
            'total_delay_seconds' => (int) $order->total_delay_seconds,
            'customer_delay_count'=> (int) $order->customer_delay_count,
            'remaining_delay_seconds' => max(0, self::MAX_CUSTOMER_DELAY_SECONDS - (int) $order->total_delay_seconds),
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
