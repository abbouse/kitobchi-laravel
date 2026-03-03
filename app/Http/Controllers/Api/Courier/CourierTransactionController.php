<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\CourierTransaction;
use App\Models\CommissionSetting;
use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourierTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:courier');
    }

    public function requestWithdrawal()
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if ($courier->balance <= 0) {
            return response()->json([
                'success' => false, 
                'message' => 'Yechib olish uchun balans yetarli emas'
            ], 400);
        }
        $commissionSetting = CommissionSetting::where('priceFrom', '<=', $courier->balance)
            ->where('priceTo', '>=', $courier->balance)
            ->first();
        if (!$commissionSetting) {
            return response()->json([
                'success' => false, 
                'message' => 'Komissiya sozlamalari topilmadi'
            ], 400);
        }
        $commissionPercent = $commissionSetting->percent;
        $commissionPrice = ($courier->balance * $commissionPercent) / 100;
        $netAmount = $courier->balance - $commissionPrice;
        $transaction = CourierTransaction::create([
            'courier_id' => $courier->id,
            'card' => $courier->payment_card,
            'amount' => $courier->balance,
            'commissionPercent' => $commissionPercent,
            'commissionPrice' => $commissionPrice,
            'netAmount' => $netAmount,
            'status' => 'pending',
        ]);
        $courier->balance = 0;
        $courier->save();
        return response()->json([
            'success' => true,
            'message' => 'Yechib olish so\'rovi muvaffaqiyatli yuborildi',
            'transaction_id' => $transaction->id,
        ], 200);
    }

    public function getTransactions()
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $transactions = CourierTransaction::where('courier_id', $courier->id)
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
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'total_paid' => (int) $courier->total_withdrawal,
                'balance' => (int) $courier->balance
            ]
        ], 200);
    }

    public function cancelTransaction(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $transactionId = $request->input('transaction_id');
        if (!$transactionId) {
            return response()->json([
                'success' => false, 
                'message' => 'Tranzaksiya ID si kiritilmadi'
            ], 400);
        }
        $transaction = CourierTransaction::where('courier_id', $courier->id)
            ->where('id', $transactionId)
            ->where('status', 'pending')
            ->first();
        if (!$transaction) {
            return response()->json([
                'success' => false, 
                'message' => '#'.$transactionId.' Tranzaksiya topilmadi yoki bekor qilinishi mumkin emas'
            ], 404);
        }
        $transaction->update([
            'status' => 'rejected',
            'rejected_desc' => "Sotuvchi tomonidan ariza qayta ishlash jarayonida bekor qilindi, pullar hisobga qaytarildi."
        ]);
        $oldBalance = $courier->balance;
        $courier->balance += $transaction->amount;
        $courier->save();
        return response()->json([
            'success' => true,
            'message' => 'Tranzaksiya muvaffaqiyatli bekor qilindi',
            'transaction_id' => $transaction->id,
        ], 200);
    }
}