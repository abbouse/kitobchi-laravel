<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\CommissionSetting;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\SellerStaffLog;
use App\Models\SellerTransaction; // ✅ LOG MODEL
use App\Services\SellerPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function __construct(private readonly SellerPayoutService $payouts)
    {
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
     * ✅ TRANSACTION ACCESS: OWNER + ACCOUNTANT (role=4)
     */
    private function hasTransactionAccess($seller)
    {
        return ! $seller->parent_id || $seller->role == 4;
    }

    private function canManageWithdrawals($seller): bool
    {
        if (! $seller->parent_id) {
            return true;
        }

        return (int) $seller->role === 4 && (bool) $seller->can_withdraw_balance;
    }

    private function applyStaffBranchScope($query, $seller)
    {
        if (! $seller->parent_id || $this->canManageWithdrawals($seller)) {
            return $query;
        }

        $location = SellerLocation::query()
            ->whereKey($seller->seller_location_id)
            ->where('seller_id', $this->getStoreSellerId($seller))
            ->where('is_deleted', false)
            ->first();

        if (! $location) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('sellerOrder', function ($orders) use ($location) {
            $jsonLocation = "CAST(JSON_UNQUOTE(JSON_EXTRACT(seller_orders.address, '$[0].location_id')) AS UNSIGNED)";

            if ($location->is_main) {
                $orders->where(function ($inner) use ($jsonLocation, $location) {
                    $inner->whereRaw("{$jsonLocation} = ?", [$location->id])
                        ->orWhere(function ($legacy) use ($jsonLocation) {
                            $legacy->whereRaw("{$jsonLocation} IS NULL")
                                ->where(function ($delivery) {
                                    $delivery->whereRaw("LOWER(COALESCE(seller_orders.delivery_type, '')) != ?", ['pickup'])
                                        ->orWhereNull('seller_orders.delivery_type');
                                });
                        });
                });

                return;
            }

            $orders->whereRaw("{$jsonLocation} = ?", [$location->id]);
        });
    }

    /**
     * ✅ LOG YOZISH FUNKSIYASI
     */
    private function writeLog($staff, $action, $details = '')
    {
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'text' => "Hodim: {$staff->firstname} {$staff->lastname} ({$staff->role}) → {$action}".($details ? " | {$details}" : ''),
        ]);
    }

    private function resolveCommissionPercent(Seller $storeSeller, int $amount): ?int
    {
        if ((int) ($storeSeller->commission_percent ?? 0) > 0) {
            return min(100, (int) $storeSeller->commission_percent);
        }

        $commissionSetting = CommissionSetting::where('priceFrom', '<=', $amount)
            ->where('priceTo', '>=', $amount)
            ->first();

        return $commissionSetting ? (int) $commissionSetting->percent : null;
    }

    private function resolveWithdrawalMethod(Seller $seller): ?string
    {
        if (filled($seller->payment_card)) {
            return (string) $seller->payment_card;
        }

        if (filled($seller->bank_account)) {
            $parts = array_filter([
                filled($seller->bank_name) ? trim((string) $seller->bank_name) : null,
                'Hisob: '.($seller->masked_bank_account ?: $seller->bank_account),
                filled($seller->bank_mfo) ? 'MFO: '.trim((string) $seller->bank_mfo) : null,
            ]);

            return implode(' · ', $parts);
        }

        return null;
    }

    /** Ilovada ko'rsatish uchun: karta yoki hisob raqamining oxirgi raqamlari. */
    private function maskedWithdrawalMethod(Seller $seller): ?array
    {
        if (filled($seller->payment_card)) {
            $digits = preg_replace('/\D+/', '', (string) $seller->payment_card);

            return [
                'type' => 'card',
                'label' => 'Karta',
                'masked' => $digits !== '' ? '•••• '.substr($digits, -4) : (string) $seller->payment_card,
            ];
        }

        if (filled($seller->bank_account)) {
            return [
                'type' => 'bank',
                'label' => filled($seller->bank_name) ? trim((string) $seller->bank_name) : 'Bank hisobi',
                'masked' => (string) ($seller->masked_bank_account ?: $seller->bank_account),
            ];
        }

        return null;
    }

    /**
     * Balansdan yechish so'rovi.
     *
     * Faqat "yechish mumkin" qismi yechiladi: oxirgi `hold_days` kunda
     * yakunlangan buyurtmalar puli ushlab turiladi (SellerPayoutService).
     * `amount` berilmasa — yechish mumkin bo'lgan butun summa.
     */
    public function requestWithdrawal(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (! $this->hasTransactionAccess($seller) || ! $this->canManageWithdrawals($seller)) {
            $this->writeLog($seller, 'Pul yechib olishga urindi (RUXSAT YO\'Q)');

            return response()->json([
                'success' => false,
                'message' => 'Pul yechish faqat do\'kon egasi yoki ruxsat berilgan buxgalter uchun ochiq.',
            ], 403);
        }

        $request->validate([
            'amount' => ['nullable', 'integer', 'min:1'],
        ]);
        $requestedAmount = $request->filled('amount') ? (int) $request->input('amount') : null;

        $storeSellerId = $this->getStoreSellerId($seller);
        $transaction = DB::transaction(function () use ($storeSellerId, $seller, $requestedAmount) {
            $storeSeller = Seller::query()->lockForUpdate()->find($storeSellerId);
            if (! $storeSeller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Do\'kon topilmadi',
                ], 404);
            }

            $summary = $this->payouts->summary($storeSeller);
            $withdrawable = (int) $summary['withdrawable'];
            $minimum = (int) $summary['min_withdrawal'];
            $amount = $requestedAmount ?? $withdrawable;

            if ($withdrawable <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => $summary['held'] > 0
                        ? "Hozircha yechish mumkin bo'lgan summa yo'q. Buyurtma puli yakunlangandan {$summary['hold_days']} kun o'tib yechishga ochiladi."
                        : 'Yechib olish uchun balans yetarli emas',
                    'data' => $summary,
                ], 400);
            }

            if ($amount > $withdrawable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Eng ko\'pi bilan '.number_format($withdrawable, 0, '.', ' ').' so\'m yechish mumkin',
                    'data' => $summary,
                ], 400);
            }

            if ($amount < $minimum) {
                return response()->json([
                    'success' => false,
                    'message' => 'Eng kam yechish summasi '.number_format($minimum, 0, '.', ' ').' so\'m',
                    'data' => $summary,
                ], 400);
            }

            $withdrawalMethod = $this->resolveWithdrawalMethod($storeSeller);
            if (! $withdrawalMethod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avval to‘lov kartasi yoki bank hisob raqamini kiriting',
                ], 400);
            }

            $transaction = SellerTransaction::create([
                'seller_id' => $storeSellerId,
                'card' => $withdrawalMethod,
                'type' => 'expense',
                'category' => \App\Services\SellerOrderSettlementService::CATEGORY_WITHDRAWAL,
                'amount' => $amount,
                'commissionPercent' => 0,
                'commissionPrice' => 0,
                'netAmount' => $amount,
                'status' => 'pending',
                'description' => $seller->parent_id
                    ? "Balansdan yechish so'rovi | Hodim: {$seller->firstname} {$seller->lastname} (#{$seller->id})"
                    : "Balansdan yechish so'rovi",
            ]);

            $storeSeller->balance = (int) $storeSeller->balance - $amount;
            $storeSeller->save();

            $this->writeLog($seller, 'Pul yechib olish so\'rovi yubordi',
                "Miqdor: {$amount} UZS, Tranzaksiya: #{$transaction->id}"
            );

            return $transaction;
        });

        if ($transaction instanceof \Illuminate\Http\JsonResponse) {
            return $transaction;
        }

        return response()->json([
            'success' => true,
            'message' => 'Yechib olish so\'rovi muvaffaqiyatli yuborildi',
            'transaction_id' => $transaction->id,
            'amount' => (int) $transaction->amount,
        ], 200);
    }

    public function getTransactions()
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (! $this->hasTransactionAccess($seller)) {
            return response()->json(['success' => true, 'data' => []], 200);
        }
        $storeSellerId = $this->getStoreSellerId($seller);
        $query = SellerTransaction::where('seller_id', $storeSellerId);
        $this->applyStaffBranchScope($query, $seller);
        $transactions = $query
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Buyurtma daromadi qachon yechishga ochiladi
        $now = now();
        $data = $transactions->map(function (SellerTransaction $transaction) use ($now) {
            $availableAt = $transaction->status === SellerTransaction::STATUS_APPROVED
                ? $this->payouts->availableAt($transaction)
                : null;

            return $transaction->toArray() + [
                'available_at' => $availableAt?->toIso8601String(),
                'on_hold' => $availableAt !== null && $availableAt->greaterThan($now),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function getTotal()
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (! $this->hasTransactionAccess($seller)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_paid' => 0,
                    'balance' => 0,
                ],
            ], 200);
        }
        $storeSellerId = $this->getStoreSellerId($seller);
        $storeSeller = Seller::find($storeSellerId);

        if ($seller->parent_id && ! $this->canManageWithdrawals($seller)) {
            $query = SellerTransaction::query()
                ->where('seller_id', $storeSellerId)
                ->where('type', 'income')
                ->where('status', SellerTransaction::STATUS_APPROVED);
            $this->applyStaffBranchScope($query, $seller);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_paid' => 0,
                    'balance' => (int) $query->sum(DB::raw('COALESCE(netAmount, amount)')),
                    'can_withdraw' => false,
                    'is_branch_total' => true,
                ],
            ], 200);
        }

        $summary = $this->payouts->summary($storeSeller);

        return response()->json([
            'success' => true,
            'data' => [
                'total_paid' => $summary['total_withdrawn'],
                'balance' => $summary['balance'],
                'withdrawable_balance' => $summary['withdrawable'],
                'held_balance' => $summary['held'],
                'pending_withdrawal' => $summary['pending_withdrawal'],
                'hold_days' => $summary['hold_days'],
                'min_withdrawal' => $summary['min_withdrawal'],
                'next_release' => $summary['next_release'],
                'upcoming_releases' => $summary['upcoming'],
                'withdrawal_method' => $this->maskedWithdrawalMethod($storeSeller),
                'can_withdraw' => $this->canManageWithdrawals($seller),
                'is_store_total' => (bool) $seller->parent_id,
            ],
        ], 200);
    }

    public function cancelTransaction(Request $request, ?int $transactionId = null)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (! $this->hasTransactionAccess($seller) || ! $this->canManageWithdrawals($seller)) {
            $this->writeLog($seller, 'Tranzaksiyani bekor qilishga urindi (RUXSAT YO\'Q)');

            return response()->json([
                'success' => false,
                'message' => 'Tranzaksiyani bekor qilish faqat do\'kon egasi yoki ruxsat berilgan buxgalter uchun ochiq.',
            ], 403);
        }

        $transactionId = $transactionId ?: (int) $request->input('transaction_id');
        if (! $transactionId) {
            return response()->json([
                'success' => false,
                'message' => 'Tranzaksiya ID si kiritilmadi',
            ], 400);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $transaction = SellerTransaction::where('seller_id', $storeSellerId)
            ->where('id', $transactionId)
            ->where('status', 'pending')
            ->first();
        if (! $transaction) {
            $this->writeLog($seller, 'Tranzaksiyani bekor qilishga urindi (TOPILMADI)', "#{$transactionId}");

            return response()->json([
                'success' => false,
                'message' => '#'.$transactionId.' Tranzaksiya topilmadi yoki bekor qilinishi mumkin emas',
            ], 404);
        }
        $restoredBalance = DB::transaction(function () use ($storeSellerId, $transaction, $seller, $transactionId) {
            $lockedTransaction = SellerTransaction::query()->lockForUpdate()->find($transaction->id);
            $storeSeller = Seller::query()->lockForUpdate()->find($storeSellerId);

            if (! $lockedTransaction || $lockedTransaction->status !== 'pending' || ! $storeSeller) {
                return null;
            }

            $lockedTransaction->update([
                'status' => 'rejected',
                'rejected_desc' => 'Sotuvchi tomonidan ariza qayta ishlash jarayonida bekor qilindi, pullar hisobga qaytarildi.',
            ]);

            $oldBalance = (int) $storeSeller->balance;
            $storeSeller->balance = $oldBalance + (int) $lockedTransaction->amount;
            $storeSeller->save();

            $this->writeLog($seller, 'Tranzaksiyani bekor qildi',
                "#{$transactionId} | Miqdor: {$lockedTransaction->amount} UZS | Balans: {$oldBalance} → {$storeSeller->balance} UZS"
            );

            return (int) $storeSeller->balance;
        });

        if ($restoredBalance === null) {
            return response()->json([
                'success' => false,
                'message' => '#'.$transactionId.' Tranzaksiya topilmadi yoki bekor qilinishi mumkin emas',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tranzaksiya muvaffaqiyatli bekor qilindi',
            'transaction_id' => $transaction->id,
        ], 200);
    }
}
