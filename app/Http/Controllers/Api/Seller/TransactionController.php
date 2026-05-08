<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerTransaction;
use App\Models\CommissionSetting;
use App\Models\Seller;
use App\Models\SellerStaffLog; // ✅ LOG MODEL
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function __construct()
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
        return !$seller->parent_id || $seller->role == 4;
    }

    /**
     * ✅ LOG YOZISH FUNKSIYASI
     */
    private function writeLog($staff, $action, $details = '')
    {
        $storeSellerId = $this->getStoreSellerId($staff);
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'text' => "Hodim: {$staff->firstname} {$staff->lastname} ({$staff->role}) → {$action}" . ($details ? " | {$details}" : ''),
        ]);
    }

    private function resolveCommissionPercent(Seller $storeSeller, int $amount): ?int
    {
        if ($storeSeller->commission_percent !== null) {
            return max(0, min(100, (int) $storeSeller->commission_percent));
        }

        $commissionSetting = CommissionSetting::where('priceFrom', '<=', $amount)
            ->where('priceTo', '>=', $amount)
            ->first();

        return $commissionSetting ? (int) $commissionSetting->percent : null;
    }

    public function requestWithdrawal()
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->hasTransactionAccess($seller)) {
            $this->writeLog($seller, 'Pul yechib olishga urindi (RUXSAT YO\'Q)');
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Withdrawal available only for Owner and Accountant.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $transaction = DB::transaction(function () use ($storeSellerId, $seller) {
            $storeSeller = Seller::query()->lockForUpdate()->find($storeSellerId);
            if (!$storeSeller || (int) $storeSeller->balance <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yechib olish uchun balans yetarli emas'
                ], 400);
            }

            if (empty($storeSeller->payment_card)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avval to‘lov kartasini kiriting'
                ], 400);
            }

            $amount = (int) $storeSeller->balance;

            $transaction = SellerTransaction::create([
                'seller_id' => $storeSellerId,
                'card' => $storeSeller->payment_card,
                'type' => 'expense',
                'category' => \App\Services\SellerOrderSettlementService::CATEGORY_WITHDRAWAL,
                'amount' => $amount,
                'commissionPercent' => 0,
                'commissionPrice' => 0,
                'netAmount' => $amount,
                'status' => 'pending',
                'description' => "Balansdan yechish so'rovi",
            ]);

            $storeSeller->balance = 0;
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
        ], 200);
    }

    public function getTransactions()
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $storeSellerId = $this->getStoreSellerId($seller);
        $transactions = SellerTransaction::where('seller_id', $storeSellerId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        return response()->json([
            'success' => true, 
            'data' => $transactions
        ], 200);
    }

    public function getTotal()
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->hasTransactionAccess($seller)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_paid' => 0,
                    'balance' => 0
                ]
            ], 200);
        }
        $storeSellerId = $this->getStoreSellerId($seller);
        $storeSeller = Seller::find($storeSellerId);
        return response()->json([
            'success' => true,
            'data' => [
                'total_paid' => (int) $storeSeller->total_withdrawal,
                'balance' => (int) $storeSeller->balance
            ]
        ], 200);
    }

    public function cancelTransaction(Request $request, ?int $transactionId = null)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->hasTransactionAccess($seller)) {
            $this->writeLog($seller, 'Tranzaksiyani bekor qilishga urindi (RUXSAT YO\'Q)');
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Transaction cancel available only for Owner and Accountant.'
            ], 403);
        }

        $transactionId = $transactionId ?: (int) $request->input('transaction_id');
        if (!$transactionId) {
            return response()->json([
                'success' => false, 
                'message' => 'Tranzaksiya ID si kiritilmadi'
            ], 400);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $transaction = SellerTransaction::where('seller_id', $storeSellerId)
            ->where('id', $transactionId)
            ->where('status', 'pending')
            ->first();
        if (!$transaction) {
            $this->writeLog($seller, 'Tranzaksiyani bekor qilishga urindi (TOPILMADI)', "#{$transactionId}");
            return response()->json([
                'success' => false, 
                'message' => '#'.$transactionId.' Tranzaksiya topilmadi yoki bekor qilinishi mumkin emas'
            ], 404);
        }
        $restoredBalance = DB::transaction(function () use ($storeSellerId, $transaction, $seller, $transactionId) {
            $lockedTransaction = SellerTransaction::query()->lockForUpdate()->find($transaction->id);
            $storeSeller = Seller::query()->lockForUpdate()->find($storeSellerId);

            if (!$lockedTransaction || $lockedTransaction->status !== 'pending' || !$storeSeller) {
                return null;
            }

            $lockedTransaction->update([
                'status' => 'rejected',
                'rejected_desc' => "Sotuvchi tomonidan ariza qayta ishlash jarayonida bekor qilindi, pullar hisobga qaytarildi."
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
                'message' => '#'.$transactionId.' Tranzaksiya topilmadi yoki bekor qilinishi mumkin emas'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tranzaksiya muvaffaqiyatli bekor qilindi',
            'transaction_id' => $transaction->id,
        ], 200);
    }
}
