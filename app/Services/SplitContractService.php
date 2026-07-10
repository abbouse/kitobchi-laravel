<?php

namespace App\Services;

use App\Models\Sold;
use App\Models\SplitContract;
use App\Models\SplitEvent;
use App\Models\SplitInstallment;
use App\Models\SplitPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Split shartnomalari hayotiy sikli:
 *
 *  openContractForOrder()  -> pending  (1-installment Paylov'da HOLD qilinadi)
 *  activate()              -> active   (hold yechiladi = 1-to'lov to'landi; buyurtma topshirilganda)
 *  cancelPending()         -> cancelled (hold dismiss, bekor)
 *  chargeDueInstallment()  -> keyingi to'lovlar receipt+pay bilan avto yechiladi
 *  settleEarly()           -> muddatidan oldin yopish (faqat o'tgan oylar ustamasi bilan)
 *
 * Retry siyosati: due kuni, +1, +3, +7 kun. Hammasi muvaffaqiyatsiz -> overdue.
 */
class SplitContractService
{
    /** Muvaffaqiyatsiz urinishdan keyingi kutish kunlari (attempt_count bo'yicha). */
    private const RETRY_OFFSETS_DAYS = [1, 3, 7];

    private ?array $transactionColumns = null;

    public function __construct(
        private readonly SplitScheduleService $scheduleService,
        private readonly SplitProfileService $profileService,
    ) {
    }

    /**
     * Checkout / admin: buyurtma uchun split shartnoma ochadi.
     * 1-installment kartada HOLD qilinadi, buyurtma topshirilganda activate() chaqiriladi.
     */
    public function openContractForOrder(User $user, Sold $order, SplitPlan $plan, UserCard $card): SplitContract
    {
        // Xizmat haqlari kreditga kirmaydi: to'liq 1-to'lovga qo'shiladi, foiz hisoblanmaydi.
        // Bu yetkazish (multi-seller bo'lsa har seller uchun qo'shimcha bilan birga —
        // hammasi parent orderning deliveryPrice ichida) va packaging'ni qamraydi.
        // Split faqat mahsulot qismiga ochiladi (yirik marketpleyslar modeli).
        $deliveryFee = max(0, (int) ($order->deliveryPrice ?? 0));
        $packagingFee = max(0, (int) ($order->packaging_price ?? 0));
        $serviceFees = $deliveryFee + $packagingFee;
        $principal = max(0, (int) $order->amount - $serviceFees);

        if ($principal <= 0) {
            throw new RuntimeException("Buyurtmaning mahsulot qismi splitga yetarli emas.");
        }

        $this->assertPlanUsable($plan, $principal);
        $this->assertUserEligible($user, $plan, $principal);
        $this->assertCardUsable($user, $card);
        $this->assertOrderCategoriesAllowed($order);

        if (SplitContract::query()->where('order_id', $order->id)->whereIn('status', [
            SplitContract::STATUS_PENDING,
            SplitContract::STATUS_ACTIVE,
            SplitContract::STATUS_OVERDUE,
        ])->exists()) {
            throw new RuntimeException('Bu buyurtma uchun ochiq split shartnoma allaqachon mavjud.');
        }

        $schedule = $this->scheduleService->calculate($plan, $principal, null, $serviceFees);
        $upfrontAmount = (int) $schedule['installments'][0]['amount'];

        $paylov = PaylovService::make();
        $paylov->ensureCardReadyForPayment($card);

        $holdMinutes = max(1, min(40320, (int) config('services.paylov.hold_minutes', 5760)));
        $holdCreate = $paylov->createHold(
            (string) $user->id,
            (string) $card->provider_card_id,
            $upfrontAmount,
            $holdMinutes,
            [
                'order_id' => 'SPL-NEW-'.$order->id,
                'merchant_id' => $paylov->merchantId(),
                'payment_mode' => 'hold',
            ],
        );

        $providerTransactionId = (string) data_get($holdCreate, 'result.transactionId', '');
        if ($providerTransactionId === '') {
            throw new RuntimeException('Paylov transactionId qaytarmadi.');
        }

        try {
            $contract = DB::transaction(function () use ($user, $order, $plan, $schedule, $upfrontAmount, $providerTransactionId, $holdCreate, $card, $holdMinutes, $deliveryFee, $packagingFee) {
                $contract = SplitContract::query()->create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'plan_id' => $plan->id,
                    'principal_amount' => $schedule['principal'],
                    'interest_amount' => $schedule['interest'],
                    'total_amount' => $schedule['total'],
                    'paid_amount' => 0,
                    'remaining_amount' => $schedule['total'],
                    'months' => $schedule['months'],
                    'period_unit' => $schedule['period_unit'],
                    'period_every' => $schedule['period_every'],
                    'monthly_interest_percent' => $schedule['monthly_interest_percent'],
                    'installments_count' => $schedule['installments_count'],
                    'debit_day' => $schedule['debit_day'],
                    'status' => SplitContract::STATUS_PENDING,
                    'starts_at' => now(),
                    'snapshot' => [
                        'plan' => $plan->only(['id', 'name', 'months', 'period_unit', 'period_every', 'monthly_interest_percent']),
                        'schedule' => $schedule,
                    ],
                    'meta' => [
                        'delivery_fee' => $deliveryFee,
                        'packaging_fee' => $packagingFee,
                        'service_fees' => (int) $schedule['upfront_extra'],
                    ],
                ]);

                foreach ($schedule['installments'] as $row) {
                    SplitInstallment::query()->create([
                        'contract_id' => $contract->id,
                        'user_id' => $user->id,
                        'sequence' => $row['sequence'],
                        'amount' => $row['amount'],
                        'is_upfront' => $row['is_upfront'],
                        'due_at' => $row['due_at'],
                        'status' => SplitInstallment::STATUS_PENDING,
                        'provider_transaction_id' => $row['is_upfront'] ? $providerTransactionId : null,
                    ]);
                }

                $transactionRowId = $this->insertTransaction([
                    'owner_id' => $user->id,
                    'order_id' => $order->id,
                    'amount' => $upfrontAmount,
                    'payment_type' => 'split',
                    'state' => 1,
                    'create_time' => now()->format('Y-m-d H:i:s'),
                    'paycom_transaction_id' => $providerTransactionId,
                    'paycom_time_datetime' => now()->format('Y-m-d H:i:s'),
                    'provider' => 'paylov',
                    'provider_transaction_id' => $providerTransactionId,
                    'provider_card_id' => $card->provider_card_id,
                    'provider_response' => [
                        'create' => $holdCreate,
                        'mode' => 'hold',
                        'split_contract_id' => $contract->id,
                        'split_installment_sequence' => 1,
                        'hold' => [
                            'status' => 'held',
                            'amount' => $upfrontAmount,
                            'hold_minutes' => $holdMinutes,
                            'created_at' => now()->toIso8601String(),
                        ],
                        'card_snapshot' => $this->cardSnapshot($card),
                    ],
                    'receivers' => [],
                ]);

                $contract->installments()->where('sequence', 1)->update(['transaction_id' => $transactionRowId]);

                return $contract;
            });
        } catch (\Throwable $e) {
            try {
                $paylov->dismissHold($providerTransactionId);
            } catch (\Throwable $dismissError) {
                Log::warning('[Split] Hold dismiss after open failure failed', [
                    'order_id' => $order->id,
                    'transaction_id' => $providerTransactionId,
                    'error' => $dismissError->getMessage(),
                ]);
            }

            throw $e;
        }

        SplitEvent::record('contract_opened', $contract->id, null, $user->id, [
            'order_id' => $order->id,
            'plan_id' => $plan->id,
            'total' => $contract->total_amount,
            'upfront' => $upfrontAmount,
        ]);

        // Buyurtmani HELD holatiga o'tkazamiz — unpaid-cancel cron o'chirmasligi va
        // fulfillment oqimi odatdagidek davom etishi uchun (pay-with-card bilan bir xil).
        try {
            DB::transaction(function () use ($order) {
                $freshOrder = Sold::query()->lockForUpdate()->find($order->id);
                if (
                    $freshOrder
                    && \App\Enums\PaymentStatusCode::fromLegacy($freshOrder->payment_status_code ?? $freshOrder->paymentStatus) === \App\Enums\PaymentStatusCode::CARD_PENDING
                ) {
                    app(OrderService::class)->handleOrderHeld($freshOrder);
                }
            });
        } catch (\Throwable $e) {
            // Order held bo'lmasa shartnomani qoldirib bo'lmaydi — hold qaytariladi.
            try {
                $this->cancelPending($contract->refresh(), 'order_held_failed');
            } catch (\Throwable $cancelError) {
                Log::error('[Split] Rollback after held failure failed', [
                    'contract_id' => $contract->id,
                    'error' => $cancelError->getMessage(),
                ]);
            }

            throw $e;
        }

        $this->safeRefreshProfile($user);

        return $contract;
    }

    /**
     * Buyurtma topshirilganda: upfront hold yechiladi, shartnoma faollashadi.
     */
    public function activate(SplitContract $contract): SplitContract
    {
        if ($contract->status !== SplitContract::STATUS_PENDING) {
            throw new RuntimeException('Faqat pending shartnomani faollashtirish mumkin.');
        }

        /** @var SplitInstallment|null $upfront */
        $upfront = $contract->installments()->where('is_upfront', true)->first();
        if (! $upfront || blank($upfront->provider_transaction_id)) {
            throw new RuntimeException('Upfront hold topilmadi.');
        }

        $paylov = PaylovService::make();
        $chargeResponse = $paylov->chargeHold((string) $upfront->provider_transaction_id, (int) $upfront->amount);

        $this->markInstallmentPaid($upfront, (int) $upfront->amount, $chargeResponse);

        if ($upfront->transaction_id) {
            $this->updateTransactionRow((int) $upfront->transaction_id, [
                'state' => 2,
                'perform_time' => now()->format('Y-m-d H:i:s'),
                'perform_time_unix' => time(),
            ], fn (array $response) => array_merge($response, [
                'charge' => $chargeResponse,
                'hold' => array_merge($response['hold'] ?? [], [
                    'status' => 'charged',
                    'charged_amount' => (int) $upfront->amount,
                    'charged_at' => now()->toIso8601String(),
                    'charge_reason' => 'split_upfront',
                ]),
            ]));
        }

        $contract->forceFill([
            'status' => SplitContract::STATUS_ACTIVE,
            'activated_at' => now(),
        ])->save();

        $this->recalculateContractTotals($contract);

        SplitEvent::record('contract_activated', $contract->id, $upfront->id, $contract->user_id, [
            'upfront_amount' => (int) $upfront->amount,
        ]);

        $this->safeRefreshProfile($contract->user);

        return $contract->refresh();
    }

    /**
     * Pending shartnomani bekor qilish: hold dismiss, hamma narsa cancelled.
     */
    public function cancelPending(SplitContract $contract, string $reason = 'cancelled_by_admin'): SplitContract
    {
        if ($contract->status !== SplitContract::STATUS_PENDING) {
            throw new RuntimeException('Faqat pending shartnomani bekor qilish mumkin.');
        }

        /** @var SplitInstallment|null $upfront */
        $upfront = $contract->installments()->where('is_upfront', true)->first();

        if ($upfront && filled($upfront->provider_transaction_id)) {
            try {
                PaylovService::make()->dismissHold((string) $upfront->provider_transaction_id);
            } catch (\Throwable $e) {
                Log::warning('[Split] Cancel dismiss failed', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($upfront->transaction_id) {
                $this->updateTransactionRow((int) $upfront->transaction_id, [
                    'state' => -1,
                    'reason' => 0,
                    'cancel_time' => (string) intval(round(microtime(true) * 1000)),
                ], fn (array $response) => array_merge($response, [
                    'hold' => array_merge($response['hold'] ?? [], [
                        'status' => 'dismissed',
                        'dismiss_reason' => $reason,
                        'dismissed_at' => now()->toIso8601String(),
                    ]),
                ]));
            }
        }

        DB::transaction(function () use ($contract, $reason) {
            $contract->installments()
                ->whereNotIn('status', [SplitInstallment::STATUS_PAID])
                ->update(['status' => SplitInstallment::STATUS_CANCELLED]);

            $contract->forceFill([
                'status' => SplitContract::STATUS_CANCELLED,
                'closed_at' => now(),
                'remaining_amount' => 0,
                'meta' => array_merge($contract->meta ?? [], ['cancel_reason' => $reason]),
            ])->save();
        });

        SplitEvent::record('contract_cancelled', $contract->id, null, $contract->user_id, ['reason' => $reason]);
        $this->safeRefreshProfile($contract->user);

        return $contract->refresh();
    }

    /**
     * Navbatdagi installmentni avto yechish (cron yoki admin "hozir yech").
     * Default verified karta -> boshqa verified kartalar (recovery pool).
     */
    public function chargeDueInstallment(SplitInstallment $installment): bool
    {
        $contract = $installment->contract;

        if (! $contract || ! in_array($contract->status, [SplitContract::STATUS_ACTIVE, SplitContract::STATUS_OVERDUE], true)) {
            return false;
        }

        if (! in_array($installment->status, [SplitInstallment::STATUS_PENDING, SplitInstallment::STATUS_OVERDUE], true)) {
            return false;
        }

        $user = $contract->user;
        if (! $user) {
            return false;
        }

        $amount = max(0, (int) $installment->amount - (int) $installment->paid_amount);
        if ($amount <= 0) {
            $this->markInstallmentPaid($installment, 0, ['note' => 'zero_remaining']);
            $this->recalculateContractTotals($contract);

            return true;
        }

        $cards = UserCard::query()
            ->where('user_id', $user->id)
            ->where('is_verified', true)
            ->whereNotNull('provider_card_id')
            ->where(function ($query) {
                $query->whereNull('is_temporary')->orWhere('is_temporary', false);
            })
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();

        $lastError = null;

        foreach ($cards as $card) {
            try {
                $this->payInstallmentWithCard($installment, $contract, $user, $card, $amount);

                return true;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();

                Log::info('[Split] Installment charge attempt failed', [
                    'installment_id' => $installment->id,
                    'card_id' => $card->id,
                    'error' => $lastError,
                ]);
            }
        }

        $this->registerFailedAttempt($installment, $contract, $lastError ?? 'no_usable_card');

        return false;
    }

    /**
     * Qisman bekor qilish krediti: seller mahsulotni "qolmadi" desa yoki
     * multi-seller buyurtmada bitta seller bekor qilsa chaqiriladi.
     *
     * Model (Klarna/Tabby uslubi): pul naqd qaytarilmaydi — kredit KELAJAKDAGI
     * to'lovlardan, eng yaqinidan boshlab kaskad bilan ayiriladi:
     *   keyingi oy 50 000, kredit 59 000 -> keyingi oy 0 (waived), 9 000
     *   undan keyingi oydan ayiriladi.
     *
     * Kredit tarkibi:
     *   - mahsulot narxi (productAmount)
     *   - shu mahsulotga to'g'ri kelgan ustama: productAmount x oylik% x oy
     *     (mijoz bekor bo'lgan mahsulot uchun foiz to'lamaydi)
     *   - deliveryCredit: masalan multi-seller per-seller yetkazish qo'shimchasi
     *
     * To'liq yopilgan oy uchun "to'lov qiling" push bormaydi — o'rniga
     * "bu oy yopildi" xabari yuboriladi.
     *
     * @return array{credit:int, interest_credit:int, applied:int, refund_due:int}
     */
    public function applyCancellationCredit(
        SplitContract $contract,
        int $productAmount,
        int $deliveryCredit = 0,
        string $reason = 'item_cancelled',
    ): array {
        if ($contract->status === SplitContract::STATUS_PENDING) {
            // Pendingda hold summasi qotib qolgan — to'g'ri yo'l: bekor qilib qayta ochish.
            throw new RuntimeException("Pending shartnomaga kredit qo'llanmaydi. Uni bekor qilib, yangi summa bilan qayta oching.");
        }

        if (! in_array($contract->status, [SplitContract::STATUS_ACTIVE, SplitContract::STATUS_OVERDUE], true)) {
            throw new RuntimeException('Faqat faol shartnomaga kredit qo\'llash mumkin.');
        }

        $productAmount = max(0, $productAmount);
        $deliveryCredit = max(0, $deliveryCredit);

        if ($productAmount > (int) $contract->principal_amount) {
            throw new RuntimeException('Kredit summasi shartnoma mahsulot qiymatidan katta bo\'lishi mumkin emas.');
        }

        // Bekor bo'lgan mahsulotga to'g'ri kelgan ustama ham kechiriladi.
        // YAXLITLASH: jadval bilan bir xil qoida — 100 so'mga karrali.
        $interestCredit = SplitScheduleService::roundTo100(
            $productAmount * (float) $contract->monthly_interest_percent * (int) $contract->months / 100
        );
        // Yaxlitlash shartnomadagi umumiy ustamadan oshirmasin
        $interestCredit = min($interestCredit, max(0, (int) $contract->interest_amount));
        $totalCredit = $productAmount + $interestCredit + $deliveryCredit;

        if ($totalCredit <= 0) {
            return ['credit' => 0, 'interest_credit' => 0, 'applied' => 0, 'refund_due' => 0];
        }

        $coveredInstallments = [];

        $result = DB::transaction(function () use ($contract, $productAmount, $interestCredit, $deliveryCredit, $totalCredit, $reason, &$coveredInstallments) {
            $remainingCredit = $totalCredit;

            // Kaskad: ochiq installmentlar bo'yicha eng yaqinidan boshlab.
            $openInstallments = $contract->installments()
                ->whereIn('status', [SplitInstallment::STATUS_PENDING, SplitInstallment::STATUS_OVERDUE])
                ->orderBy('sequence')
                ->lockForUpdate()
                ->get();

            foreach ($openInstallments as $installment) {
                if ($remainingCredit <= 0) {
                    break;
                }

                $payable = max(0, (int) $installment->amount - (int) $installment->paid_amount);
                $applied = min($payable, $remainingCredit);
                $remainingCredit -= $applied;

                $meta = $installment->meta ?? [];
                $meta['original_amount'] = $meta['original_amount'] ?? (int) $installment->amount;
                $meta['credit_applied'] = (int) ($meta['credit_applied'] ?? 0) + $applied;
                $meta['credit_reason'] = $reason;

                $newAmount = (int) $installment->amount - $applied;

                if ($newAmount <= (int) $installment->paid_amount) {
                    // To'liq yopildi — waived (scoring "paid" deb hisoblamaydi, avto yechim urinmaydi).
                    $installment->forceFill([
                        'amount' => $newAmount,
                        'status' => SplitInstallment::STATUS_WAIVED,
                        'next_attempt_at' => null,
                        'meta' => array_merge($meta, ['covered_by_credit' => true]),
                    ])->save();

                    $coveredInstallments[] = $installment;
                } else {
                    $installment->forceFill([
                        'amount' => $newAmount,
                        'meta' => $meta,
                    ])->save();
                }
            }

            // Ochiq to'lovlar yetmadi -> ortiq qismi naqd refund bo'lishi kerak
            // (mavjud Paylov P2P refund oqimi orqali, admin qarori bilan).
            $refundDue = $remainingCredit;

            $contractMeta = $contract->meta ?? [];
            $contractMeta['cancellation_credits'] = array_merge(
                (array) ($contractMeta['cancellation_credits'] ?? []),
                [[
                    'reason' => $reason,
                    'product_amount' => $productAmount,
                    'interest_credit' => $interestCredit,
                    'delivery_credit' => $deliveryCredit,
                    'total' => $totalCredit,
                    'refund_due' => $refundDue,
                    'applied_at' => now()->toIso8601String(),
                ]],
            );

            if ($refundDue > 0) {
                $contractMeta['refund_due'] = (int) ($contractMeta['refund_due'] ?? 0) + $refundDue;
            }

            if ($deliveryCredit > 0) {
                $contractMeta['service_fees'] = max(0, (int) ($contractMeta['service_fees'] ?? 0) - $deliveryCredit);
            }

            $contract->forceFill([
                'principal_amount' => max(0, (int) $contract->principal_amount - $productAmount),
                'interest_amount' => max(0, (int) $contract->interest_amount - $interestCredit),
                'total_amount' => max(0, (int) $contract->total_amount - ($totalCredit - $refundDue)),
                'meta' => $contractMeta,
            ])->save();

            return [
                'credit' => $totalCredit,
                'interest_credit' => $interestCredit,
                'applied' => $totalCredit - $refundDue,
                'refund_due' => $refundDue,
            ];
        });

        $this->recalculateContractTotals($contract->refresh());

        SplitEvent::record('cancellation_credit_applied', $contract->id, null, $contract->user_id, [
            'reason' => $reason,
            ...$result,
        ]);

        if ($result['refund_due'] > 0) {
            SplitEvent::record('credit_refund_due', $contract->id, null, $contract->user_id, [
                'amount' => $result['refund_due'],
            ]);
        }

        // To'liq yopilgan oylar haqida xabar ("to'lov qiling" o'rniga).
        $pushService = app(SplitPushService::class);
        foreach ($coveredInstallments as $covered) {
            $pushService->sendCoveredByCredit($contract, $covered);
        }

        return $result;
    }

    /**
     * Erta yopish kotirovkasi: muddati kelgan oylar ustamasi bilan, hali
     * kelmagan oylar esa foizsiz (faqat asosiy summa).
     *
     * @return array{payoff:int, earned_interest:int, waived_interest:int, elapsed_months:int}
     */
    public function payoffQuote(SplitContract $contract): array
    {
        // paid_amount ichida xizmat haqlari (yetkazish + packaging) ham bor —
        // kredit qismini toza ajratamiz, aks holda payoff sun'iy kichik chiqadi.
        $serviceFees = max(0, (int) data_get(
            $contract->meta,
            'service_fees',
            (int) data_get($contract->meta, 'delivery_fee', 0),
        ));

        return $this->scheduleService->earlyPayoffQuote(
            (int) $contract->principal_amount,
            (float) $contract->monthly_interest_percent,
            (int) $contract->months,
            $contract->starts_at,
            max(0, (int) $contract->paid_amount - $serviceFees),
            null,
            (string) $contract->period_unit,
            (int) $contract->period_every,
        );
    }

    /**
     * Foydalanuvchi tanlagan davrlarni tanlangan kartadan to'laydi.
     * Faqat KETMA-KET (prefix): eng yaqin to'lanmagan installmentdan boshlab
     * $upToSequence gacha. Ora-oradan bitta-bitta to'lov OLINMAYDI — shu bois
     * client nimani yuborishidan qat'i nazar, backend prefixni o'zi hisoblaydi.
     *
     * Har bir installment o'z jadval summasida (o'sha davr ustamasi bilan)
     * to'lanadi — bu foizsiz erta yopish EMAS (uni settleEarly() bajaradi),
     * balki oldindan bir necha davrni to'lash.
     *
     * @return array{paid_installments:int, charged:int, error:?string}
     */
    public function payInstallmentsUpTo(SplitContract $contract, int $upToSequence, UserCard $card): array
    {
        if (! in_array($contract->status, [SplitContract::STATUS_ACTIVE, SplitContract::STATUS_OVERDUE], true)) {
            throw new RuntimeException('Faqat faol shartnoma to\'lovlarini amalga oshirish mumkin.');
        }

        $user = $contract->user;
        if (! $user) {
            throw new RuntimeException('Shartnoma foydalanuvchisi topilmadi.');
        }

        $this->assertCardUsable($user, $card);

        $installments = $contract->installments()
            ->whereIn('status', [SplitInstallment::STATUS_PENDING, SplitInstallment::STATUS_OVERDUE])
            ->where('sequence', '<=', $upToSequence)
            ->orderBy('sequence')
            ->get();

        if ($installments->isEmpty()) {
            throw new RuntimeException('To\'lanadigan muddat topilmadi.');
        }

        $paid = 0;
        $charged = 0;
        $lastError = null;

        foreach ($installments as $installment) {
            $amount = max(0, (int) $installment->amount - (int) $installment->paid_amount);

            if ($amount <= 0) {
                $this->markInstallmentPaid($installment, 0, ['note' => 'zero_remaining']);
                $this->recalculateContractTotals($contract);
                $paid++;

                continue;
            }

            try {
                $this->payInstallmentWithCard($installment, $contract, $user, $card, $amount);
                $paid++;
                $charged += $amount;
            } catch (\Throwable $e) {
                // Prefix buzilmasligi uchun biror davr o'tmasa keyingilariga o'tmaymiz.
                $lastError = $e->getMessage();
                break;
            }
        }

        if ($paid === 0) {
            throw new RuntimeException($lastError ?? 'To\'lov amalga oshmadi.');
        }

        return [
            'paid_installments' => $paid,
            'charged' => $charged,
            'error' => $lastError,
        ];
    }

    /**
     * Muddatidan oldin to'liq yopish: faqat o'tgan oylar ustamasi olinadi,
     * qolgan ustama kechiriladi (startap uslubidagi adolatli early payoff).
     * $card berilmasa default verified karta ishlatiladi.
     */
    public function settleEarly(SplitContract $contract, ?UserCard $card = null): array
    {
        if (! in_array($contract->status, [SplitContract::STATUS_ACTIVE, SplitContract::STATUS_OVERDUE], true)) {
            throw new RuntimeException('Faqat faol shartnomani muddatidan oldin yopish mumkin.');
        }

        $serviceFees = max(0, (int) data_get(
            $contract->meta,
            'service_fees',
            (int) data_get($contract->meta, 'delivery_fee', 0),
        ));

        $quote = $this->payoffQuote($contract);

        $user = $contract->user;
        if (! $user) {
            throw new RuntimeException('Shartnoma foydalanuvchisi topilmadi.');
        }

        if ($card !== null) {
            $this->assertCardUsable($user, $card);
        }

        if ($quote['payoff'] > 0) {
            $card ??= UserCard::query()
                ->where('user_id', $user->id)
                ->where('is_verified', true)
                ->whereNotNull('provider_card_id')
                ->orderByDesc('is_default')
                ->orderBy('created_at')
                ->first();

            if (! $card) {
                throw new RuntimeException('Yechish uchun yaroqli karta topilmadi.');
            }

            $this->payArbitraryAmount($contract, $user, $card, (int) $quote['payoff'], 'split_early_payoff');
        }

        DB::transaction(function () use ($contract, $quote, $serviceFees) {
            $contract->installments()
                ->whereIn('status', [SplitInstallment::STATUS_PENDING, SplitInstallment::STATUS_OVERDUE])
                ->update(['status' => SplitInstallment::STATUS_WAIVED]);

            $contract->forceFill([
                'status' => SplitContract::STATUS_COMPLETED,
                'closed_at' => now(),
                'paid_amount' => (int) $contract->paid_amount + (int) $quote['payoff'],
                'remaining_amount' => 0,
                'interest_amount' => (int) $quote['earned_interest'],
                'total_amount' => (int) $contract->principal_amount + (int) $quote['earned_interest'] + $serviceFees,
                'overdue_since' => null,
                'meta' => array_merge($contract->meta ?? [], [
                    'early_settled' => true,
                    'early_payoff' => $quote,
                ]),
            ])->save();
        });

        SplitEvent::record('contract_early_settled', $contract->id, null, $contract->user_id, $quote);
        $this->safeRefreshProfile($user);

        return $quote;
    }

    // ─────────────────────────────────────────────────────────────────

    private function payInstallmentWithCard(
        SplitInstallment $installment,
        SplitContract $contract,
        User $user,
        UserCard $card,
        int $amount,
    ): void {
        $paylov = PaylovService::make();
        $paylov->ensureCardReadyForPayment($card);

        $receipt = $paylov->createReceipt((string) $user->id, $amount, [
            'order_id' => 'SPL-'.$contract->id.'-'.$installment->sequence,
            'merchant_id' => $paylov->merchantId(),
        ]);

        $transactionId = (string) data_get($receipt, 'result.transactionId', '');
        if ($transactionId === '') {
            throw new RuntimeException('Paylov transactionId qaytarmadi.');
        }

        $payResponse = $paylov->payReceipt($transactionId, (string) $card->provider_card_id, (string) $user->id);
        $statusResponse = [];
        try {
            $statusResponse = $paylov->getTransactions($transactionId);
        } catch (\Throwable) {
            // Status so'rovi ixtiyoriy.
        }

        $rowId = $this->insertTransaction([
            'owner_id' => $user->id,
            'order_id' => $contract->order_id,
            'amount' => $amount,
            'payment_type' => 'split',
            'state' => 2,
            'create_time' => now()->format('Y-m-d H:i:s'),
            'perform_time' => now()->format('Y-m-d H:i:s'),
            'perform_time_unix' => time(),
            'paycom_transaction_id' => $transactionId,
            'paycom_time_datetime' => now()->format('Y-m-d H:i:s'),
            'provider' => 'paylov',
            'provider_transaction_id' => $transactionId,
            'provider_card_id' => $card->provider_card_id,
            'provider_response' => [
                'create' => $receipt,
                'pay' => $payResponse,
                'status' => $statusResponse,
                'split_contract_id' => $contract->id,
                'split_installment_sequence' => $installment->sequence,
                'card_snapshot' => $this->cardSnapshot($card),
            ],
            'receivers' => [],
        ]);

        $installment->forceFill([
            'transaction_id' => $rowId,
            'provider_transaction_id' => $transactionId,
        ])->save();

        $this->markInstallmentPaid($installment, $amount, ['card_id' => $card->id]);
        $this->recalculateContractTotals($contract);

        SplitEvent::record('installment_paid', $contract->id, $installment->id, $user->id, [
            'amount' => $amount,
            'sequence' => $installment->sequence,
            'on_time' => now()->lte($installment->due_at->copy()->endOfDay()),
        ]);
    }

    private function payArbitraryAmount(
        SplitContract $contract,
        User $user,
        UserCard $card,
        int $amount,
        string $reference,
    ): void {
        $paylov = PaylovService::make();
        $paylov->ensureCardReadyForPayment($card);

        $receipt = $paylov->createReceipt((string) $user->id, $amount, [
            'order_id' => strtoupper($reference).'-'.$contract->id,
            'merchant_id' => $paylov->merchantId(),
        ]);

        $transactionId = (string) data_get($receipt, 'result.transactionId', '');
        if ($transactionId === '') {
            throw new RuntimeException('Paylov transactionId qaytarmadi.');
        }

        $payResponse = $paylov->payReceipt($transactionId, (string) $card->provider_card_id, (string) $user->id);

        $this->insertTransaction([
            'owner_id' => $user->id,
            'order_id' => $contract->order_id,
            'amount' => $amount,
            'payment_type' => 'split',
            'state' => 2,
            'create_time' => now()->format('Y-m-d H:i:s'),
            'perform_time' => now()->format('Y-m-d H:i:s'),
            'perform_time_unix' => time(),
            'paycom_transaction_id' => $transactionId,
            'paycom_time_datetime' => now()->format('Y-m-d H:i:s'),
            'provider' => 'paylov',
            'provider_transaction_id' => $transactionId,
            'provider_card_id' => $card->provider_card_id,
            'provider_response' => [
                'create' => $receipt,
                'pay' => $payResponse,
                'split_contract_id' => $contract->id,
                'reference' => $reference,
                'card_snapshot' => $this->cardSnapshot($card),
            ],
            'receivers' => [],
        ]);
    }

    private function markInstallmentPaid(SplitInstallment $installment, int $amount, array $meta = []): void
    {
        $installment->forceFill([
            'status' => SplitInstallment::STATUS_PAID,
            'paid_amount' => (int) $installment->paid_amount + $amount,
            'paid_at' => now(),
            'next_attempt_at' => null,
            'meta' => array_merge($installment->meta ?? [], ['paid' => $meta]),
        ])->save();
    }

    private function registerFailedAttempt(SplitInstallment $installment, SplitContract $contract, string $error): void
    {
        $attempt = (int) $installment->attempt_count + 1;
        $offsets = self::RETRY_OFFSETS_DAYS;

        $updates = [
            'attempt_count' => $attempt,
            'last_attempt_at' => now(),
            'meta' => array_merge($installment->meta ?? [], [
                'last_error' => $error,
            ]),
        ];

        if ($attempt <= count($offsets)) {
            // Keyingi urinish: due sanasidan emas, hozirdan offset (kunlar ketma-ketligi saqlanadi).
            $updates['next_attempt_at'] = now()->addDays($offsets[$attempt - 1])->setTime(9, 0);
            $updates['status'] = SplitInstallment::STATUS_PENDING;
        } else {
            $updates['next_attempt_at'] = null;
            $updates['status'] = SplitInstallment::STATUS_OVERDUE;
        }

        $installment->forceFill($updates)->save();

        SplitEvent::record('installment_charge_failed', $contract->id, $installment->id, $contract->user_id, [
            'attempt' => $attempt,
            'error' => $error,
        ]);

        if ($updates['status'] === SplitInstallment::STATUS_OVERDUE && $contract->status !== SplitContract::STATUS_OVERDUE) {
            $contract->forceFill([
                'status' => SplitContract::STATUS_OVERDUE,
                'overdue_since' => now(),
            ])->save();

            SplitEvent::record('contract_overdue', $contract->id, $installment->id, $contract->user_id, [
                'installment_sequence' => $installment->sequence,
            ]);

            // Overdue => darhol limit freeze (profil qayta hisoblanadi).
            if ($contract->user) {
                $this->safeRefreshProfile($contract->user);
            }
        }
    }

    private function recalculateContractTotals(SplitContract $contract): void
    {
        $paid = (int) $contract->installments()->sum('paid_amount');
        $remaining = max(0, (int) $contract->total_amount - $paid);

        $openInstallments = $contract->installments()
            ->whereIn('status', [SplitInstallment::STATUS_PENDING, SplitInstallment::STATUS_OVERDUE])
            ->count();

        $updates = [
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
        ];

        if ($openInstallments === 0 && in_array($contract->status, [SplitContract::STATUS_ACTIVE, SplitContract::STATUS_OVERDUE], true)) {
            $updates['status'] = SplitContract::STATUS_COMPLETED;
            $updates['closed_at'] = now();
            $updates['overdue_since'] = null;

            SplitEvent::record('contract_completed', $contract->id, null, $contract->user_id, [
                'paid_amount' => $paid,
            ]);
        } elseif (
            $contract->status === SplitContract::STATUS_OVERDUE
            && ! $contract->installments()->where('status', SplitInstallment::STATUS_OVERDUE)->exists()
        ) {
            // Qarzdorlik yopildi -> shartnoma yana normal holatga qaytadi.
            $updates['status'] = SplitContract::STATUS_ACTIVE;
            $updates['overdue_since'] = null;

            SplitEvent::record('contract_recovered', $contract->id, null, $contract->user_id, []);
        }

        $contract->forceFill($updates)->save();

        if ($contract->user) {
            $this->safeRefreshProfile($contract->user);
        }
    }

    private function assertPlanUsable(SplitPlan $plan, int $principal): void
    {
        if (! $plan->enabled) {
            throw new RuntimeException('Bu tarif o\'chirilgan.');
        }

        $settings = $this->profileService->settings();

        if (! $settings['enabled']) {
            throw new RuntimeException('Split moduli o\'chirilgan.');
        }

        // Min/max summa faqat tarif darajasida: bo'sh = cheklovsiz
        // (shaxsiy limit baribir yuqoridan chegaralaydi).
        $minSum = $plan->min_order_sum !== null ? (int) $plan->min_order_sum : 1000;
        $maxSum = $plan->max_order_sum !== null ? (int) $plan->max_order_sum : PHP_INT_MAX;

        if ($principal < $minSum) {
            throw new RuntimeException("Buyurtma summasi bu tarif uchun juda kichik (min: {$minSum}).");
        }

        if ($principal > $maxSum) {
            throw new RuntimeException("Buyurtma summasi bu tarif uchun juda katta (max: {$maxSum}).");
        }
    }

    private function assertUserEligible(User $user, SplitPlan $plan, int $principal): void
    {
        $profile = $this->profileService->getFreshProfile($user);

        if (! $profile['eligible']) {
            $reason = $profile['eligibility_reasons'][0] ?? 'Foydalanuvchi splitga mos emas.';
            throw new RuntimeException($reason);
        }

        if ($plan->min_confidence_score !== null && (float) $profile['confidence_score'] < (float) $plan->min_confidence_score) {
            throw new RuntimeException('Bu tarif uchun ishonch balli yetarli emas.');
        }

        if ($principal > (int) $profile['available_limit']) {
            throw new RuntimeException('Buyurtma summasi bo\'sh limitdan katta.');
        }
    }

    /**
     * Kategoriya cheklovlari umuman sozlanganmi (kamida bitta yoqilgan qoida bormi).
     */
    public function hasCategoryRestrictions(): bool
    {
        return Schema::hasTable('split_category_rules')
            && \App\Models\SplitCategoryRule::query()->where('enabled', true)->exists();
    }

    /**
     * Bitta kategoriya nasiyaga ruxsat etilganmi.
     * Cheklovlar sozlanmagan bo'lsa — hamma ruxsat.
     */
    public function categoryAllowed(string $categoryType, ?int $categoryId): bool
    {
        if (! $this->hasCategoryRestrictions()) {
            return true;
        }

        if ($categoryId === null || $categoryId <= 0) {
            return false;
        }

        return \App\Models\SplitCategoryRule::query()
            ->where('category_type', $categoryType)
            ->where('category_id', $categoryId)
            ->where('enabled', true)
            ->exists();
    }

    /**
     * Kategoriya allowlist tekshiruvi.
     * Hech bitta kategoriya yoqilmagan bo'lsa — cheklov yo'q (sozlanmagan holat).
     * Kamida bittasi yoqilgan bo'lsa — buyurtmadagi HAR BIR mahsulot kategoriyasi
     * ruxsat etilgan bo'lishi shart.
     */
    private function assertOrderCategoriesAllowed(Sold $order): void
    {
        if (! Schema::hasTable('split_category_rules')) {
            return;
        }

        $hasEnabledRules = \App\Models\SplitCategoryRule::query()->where('enabled', true)->exists();
        if (! $hasEnabledRules) {
            return;
        }

        // Itemlar: seller_order_items aniqroq manba, bo'lmasa order items json
        $items = Schema::hasTable('seller_order_items')
            ? DB::table('seller_order_items')
                ->where('order_id', $order->id)
                ->whereNull('cancelled_at')
                ->get(['product_id', 'type'])
            : collect();

        if ($items->isEmpty()) {
            $items = collect($order->items ?? [])->map(fn ($item) => (object) [
                'product_id' => (int) (((array) $item)['item_id'] ?? 0),
                'type' => (string) (((array) $item)['type'] ?? ''),
            ]);
        }

        $bookIds = [];
        $stationeryIds = [];

        foreach ($items as $item) {
            $type = strtolower((string) $item->type);
            $productId = (int) $item->product_id;

            if ($productId <= 0 || $type === 'gift') {
                continue; // 0 so'mlik sovg'alar tekshirilmaydi
            }

            if (str_contains($type, 'stationer')) {
                $stationeryIds[] = $productId;
            } else {
                $bookIds[] = $productId;
            }
        }

        $allowedBookCategories = \App\Models\SplitCategoryRule::query()
            ->where('category_type', 'book')
            ->where('enabled', true)
            ->pluck('category_id')
            ->flip();
        $allowedStationeryCategories = \App\Models\SplitCategoryRule::query()
            ->where('category_type', 'stationery')
            ->where('enabled', true)
            ->pluck('category_id')
            ->flip();

        if ($bookIds !== []) {
            $bookCategories = DB::table('books')
                ->whereIn('id', array_unique($bookIds))
                ->pluck('category_id', 'id');

            foreach ($bookCategories as $categoryId) {
                if (! $allowedBookCategories->has((int) $categoryId)) {
                    throw new RuntimeException('Buyurtmadagi ayrim mahsulotlar kategoriyasi nasiyaga ruxsat etilmagan.');
                }
            }
        }

        if ($stationeryIds !== []) {
            $stationeryCategories = DB::table('stationeries')
                ->whereIn('id', array_unique($stationeryIds))
                ->pluck('category_id', 'id');

            foreach ($stationeryCategories as $categoryId) {
                if (! $allowedStationeryCategories->has((int) $categoryId)) {
                    throw new RuntimeException('Buyurtmadagi ayrim mahsulotlar kategoriyasi nasiyaga ruxsat etilmagan.');
                }
            }
        }
    }

    private function assertCardUsable(User $user, UserCard $card): void
    {
        if ((int) $card->user_id !== (int) $user->id) {
            throw new RuntimeException('Bu karta foydalanuvchiga tegishli emas.');
        }

        if (! $card->is_verified || blank($card->provider_card_id)) {
            throw new RuntimeException('Tasdiqlanmagan karta bilan split ochib bo\'lmaydi.');
        }
    }

    private function safeRefreshProfile(?User $user): void
    {
        if (! $user) {
            return;
        }

        try {
            $this->profileService->refreshUser($user, true);
        } catch (\Throwable $e) {
            Log::warning('[Split] Profile refresh failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ── Transactions jadvali bilan ishlash (legacy ustunlarga mos) ──

    private function insertTransaction(array $payload): int
    {
        return (int) DB::table('transactions')->insertGetId($this->filterTransactionPayload($payload));
    }

    private function updateTransactionRow(int $transactionRowId, array $payload, ?callable $responseMutator = null): void
    {
        if ($responseMutator) {
            $existing = Transaction::query()->find($transactionRowId);
            $response = is_array($existing?->provider_response) ? $existing->provider_response : [];
            $payload['provider_response'] = $responseMutator($response);
        }

        $payload = $this->filterTransactionPayload($payload);

        if ($payload === []) {
            return;
        }

        DB::table('transactions')->where('id', $transactionRowId)->update($payload);
    }

    private function filterTransactionPayload(array $payload): array
    {
        $columns = $this->transactionColumns();

        return collect($payload)
            ->filter(fn ($value, $key) => in_array($key, $columns, true))
            ->map(function ($value, $key) {
                if (in_array($key, ['receivers', 'provider_response', 'perform_fiscal_data', 'cancel_fiscal_data'], true) && is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                if ($key === 'perform_time_unix' && ! is_null($value)) {
                    return (string) $value;
                }

                return $value;
            })
            ->all();
    }

    private function transactionColumns(): array
    {
        if ($this->transactionColumns !== null) {
            return $this->transactionColumns;
        }

        $this->transactionColumns = Schema::hasTable('transactions')
            ? Schema::getColumnListing('transactions')
            : [];

        return $this->transactionColumns;
    }

    private function cardSnapshot(UserCard $card): array
    {
        return [
            'masked_number' => $card->card_number,
            'vendor' => $card->vendor,
            'card_name' => $card->card_name,
            'phone_number' => $card->phone_number,
            'provider_card_id' => $card->provider_card_id,
        ];
    }
}
