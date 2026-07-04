<?php

namespace App\Services;

use App\Models\SplitPlan;
use Illuminate\Support\Carbon;

/**
 * Split to'lov jadvalini deterministik hisoblaydi.
 *
 * Qoidalar:
 * - Birinchi installment har doim "hozir" (sotib olish payti, upfront).
 * - Qolganlari sotib olish sanasidan anchor qilinadi:
 *   - oylik: addMonthsNoOverflow (31-yanvar -> 28-fevral, keyingi oylar yana 31 ga qaytmaydi,
 *     shuning uchun har bir sana anchor'dan alohida hisoblanadi);
 *   - haftalik: k * period_every hafta.
 * - Ustama marketplace uslubida: principal * oylik% * months (flat, oldindan ma'lum).
 * - Keyingi installmentlar 100 so'mga yaxlitlanadi, qoldiq birinchi to'lovga qo'shiladi
 *   (risk oldinga yuklanadi, keyingi avtomatik yechimlar "chiroyli" summa bo'ladi).
 */
class SplitScheduleService
{
    /**
     * @param int $upfrontExtra Kreditga kirmaydigan, birinchi to'lovga to'liq qo'shiladigan
     *                          summa (masalan, yetkazish haqi). Unga foiz hisoblanmaydi.
     *
     * @return array{
     *     principal:int, interest:int, upfront_extra:int, total:int,
     *     monthly_interest_percent:float, total_interest_percent:float,
     *     months:int, period_unit:string, period_every:int,
     *     installments_count:int, debit_day:?int,
     *     installments: list<array{sequence:int, amount:int, due_at:string, is_upfront:bool}>
     * }
     */
    public function calculate(SplitPlan $plan, int $principal, ?Carbon $startsAt = null, int $upfrontExtra = 0): array
    {
        $startsAt = ($startsAt ?? now())->copy();
        $principal = max(0, $principal);
        $upfrontExtra = max(0, $upfrontExtra);

        $months = max(1, (int) $plan->months);
        $every = max(1, (int) $plan->period_every);
        $unit = $plan->period_unit === SplitPlan::PERIOD_WEEK
            ? SplitPlan::PERIOD_WEEK
            : SplitPlan::PERIOD_MONTH;

        $monthlyPercent = max(0.0, (float) $plan->monthly_interest_percent);
        // Foiz faqat kredit (mahsulot) qismiga — upfrontExtra'ga (yetkazish) hisoblanmaydi.
        $interest = (int) round($principal * $monthlyPercent * $months / 100);
        $financedTotal = $principal + $interest;
        $total = $financedTotal + $upfrontExtra;

        $count = $plan->installmentsCount();
        $amounts = $this->splitAmounts($financedTotal, $count);
        // Yetkazish haqi kabi qo'shimchalar to'liq birinchi to'lovga.
        $amounts[0] += $upfrontExtra;

        $installments = [];
        foreach ($amounts as $index => $amount) {
            $dueAt = $this->dueDate($startsAt, $unit, $every, $index);

            $installments[] = [
                'sequence' => $index + 1,
                'amount' => $amount,
                'due_at' => $dueAt->toDateTimeString(),
                'is_upfront' => $index === 0,
            ];
        }

        return [
            'principal' => $principal,
            'interest' => $interest,
            'upfront_extra' => $upfrontExtra,
            'total' => $total,
            'monthly_interest_percent' => $monthlyPercent,
            'total_interest_percent' => round($monthlyPercent * $months, 2),
            'months' => $months,
            'period_unit' => $unit,
            'period_every' => $every,
            'installments_count' => $count,
            'debit_day' => $unit === SplitPlan::PERIOD_MONTH ? (int) $startsAt->day : null,
            'installments' => $installments,
        ];
    }

    /**
     * k-chi (0-based) installment sanasi. k=0 => hozir.
     * Oylik jadvalda har bir sana anchor'dan mustaqil hisoblanadi:
     * 31-yanvar anchor bo'lsa: 28-fevral, 31-mart, 30-aprel...
     */
    public function dueDate(Carbon $startsAt, string $unit, int $every, int $index): Carbon
    {
        if ($index <= 0) {
            return $startsAt->copy();
        }

        if ($unit === SplitPlan::PERIOD_WEEK) {
            return $startsAt->copy()->addWeeks($index * $every);
        }

        return $startsAt->copy()->addMonthsNoOverflow($index * $every);
    }

    /**
     * Umumiy summani n bo'lakka bo'ladi.
     * 2..n bo'laklar 100 so'mga yaxlitlanadi, farq birinchi bo'lakka qo'shiladi.
     *
     * @return list<int>
     */
    public function splitAmounts(int $total, int $count): array
    {
        $count = max(1, $count);

        if ($count === 1) {
            return [$total];
        }

        $base = intdiv($total, $count);
        $tail = $base >= 100 ? intdiv($base, 100) * 100 : $base;
        $first = $total - ($tail * ($count - 1));

        $amounts = [$first];
        for ($i = 1; $i < $count; $i++) {
            $amounts[] = $tail;
        }

        return $amounts;
    }

    /**
     * Muddatidan oldin yopish kotirovkasi (marketplace-adolatli):
     * foydalanuvchi faqat o'tgan (boshlangan) oylar uchun ustama to'laydi.
     *
     * @return array{payoff:int, earned_interest:int, waived_interest:int, elapsed_months:int}
     */
    public function earlyPayoffQuote(
        int $principal,
        float $monthlyPercent,
        int $months,
        Carbon $startsAt,
        int $alreadyPaid,
        ?Carbon $asOf = null,
    ): array {
        $asOf = ($asOf ?? now())->copy();

        // Boshlangan oy to'liq hisoblanadi (1..months oralig'ida clamp).
        $elapsedMonths = min($months, max(1, (int) $startsAt->diffInMonths($asOf) + 1));

        $earnedInterest = (int) round($principal * $monthlyPercent * $elapsedMonths / 100);
        $fullInterest = (int) round($principal * $monthlyPercent * $months / 100);
        $payoff = max(0, $principal + $earnedInterest - $alreadyPaid);

        return [
            'payoff' => $payoff,
            'earned_interest' => $earnedInterest,
            'waived_interest' => max(0, $fullInterest - $earnedInterest),
            'elapsed_months' => $elapsedMonths,
        ];
    }
}
