<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\CourierTransaction;
use App\Models\CommissionSetting;
use App\Services\CourierCashOnDeliveryCapacityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourierTransactionController extends Controller
{
    public function __construct(
        private readonly CourierCashOnDeliveryCapacityService $courierCashOnDeliveryCapacityService,
    )
    {
        $this->middleware('auth:courier');
    }

    public function requestWithdrawal()
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try {
            $transaction = DB::transaction(function () use ($courier) {
                $lockedCourier = \App\Models\Couriers::query()->lockForUpdate()->find($courier->id);
                $withdrawable = $lockedCourier
                    ? $this->courierCashOnDeliveryCapacityService->withdrawableBalance($lockedCourier)
                    : 0;

                if (!$lockedCourier || $withdrawable <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Yechib olish uchun balans yetarli emas'
                    ], 400);
                }

                if (empty($lockedCourier->payment_card)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Avval to‘lov kartasini kiriting'
                    ], 400);
                }

                $amount = (int) $withdrawable;
                $commissionSetting = CommissionSetting::where('priceFrom', '<=', $amount)
                    ->where('priceTo', '>=', $amount)
                    ->orderBy('priceFrom', 'desc')
                    ->first();

                if (!$commissionSetting) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Komissiya sozlamalari topilmadi',
                    ], 400);
                }

                $commissionPercent = $commissionSetting->percent;
                $commissionPrice   = (int) round(($amount * $commissionPercent) / 100);
                $netAmount         = max(0, $amount - $commissionPrice);

                $transaction = CourierTransaction::create([
                    'courier_id'       => $lockedCourier->id,
                    'card'             => $lockedCourier->payment_card,
                    'type'             => 'expense',
                    'category'         => 'withdrawal',
                    'amount'           => $amount,
                    'commissionPercent'=> $commissionPercent,
                    'commissionPrice'  => $commissionPrice,
                    'netAmount'        => $netAmount,
                    'status'           => 'pending',
                    'description'      => "Balansdan yechib olish so'rovi",
                ]);

                $lockedCourier->balance = max(0, (int) $lockedCourier->balance - $amount);
                $lockedCourier->save();

                return $transaction;
            });

            if ($transaction instanceof \Illuminate\Http\JsonResponse) {
                return $transaction;
            }

            return response()->json([
                'success'        => true,
                'message'        => "Yechib olish so'rovi muvaffaqiyatli yuborildi",
                'transaction_id' => $transaction->id,
            ], 200);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('Courier withdrawal request error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            $userMsg = config('app.debug') ? $th->getMessage() : "So'rovni amalga oshirishda xatolik yuz berdi. Qayta urinib ko'ring.";
            return response()->json(['success' => false, 'message' => 'Xatolik: ' . $userMsg], 500);
        }
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
            ->get()
            ->map(fn (CourierTransaction $transaction) => $this->transactionPayload($transaction));
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
                'balance' => (int) $courier->balance,
                'cod_reserved_amount' => (int) ($courier->cod_reserved_amount ?? 0),
                'withdrawable_balance' => $this->courierCashOnDeliveryCapacityService->withdrawableBalance($courier),
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
        $restoredBalance = DB::transaction(function () use ($courier, $transactionId) {
            $lockedTransaction = CourierTransaction::query()
                ->where('courier_id', $courier->id)
                ->where('id', $transactionId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$lockedTransaction) {
                return null;
            }

            $lockedCourier = \App\Models\Couriers::query()->lockForUpdate()->find($courier->id);
            if (!$lockedCourier) {
                return null;
            }

            $lockedTransaction->update([
                'status' => 'rejected',
                'rejected_desc' => "Kuryer tomonidan ariza qayta ishlash jarayonida bekor qilindi, pullar hisobga qaytarildi."
            ]);

            $lockedCourier->balance = (int) $lockedCourier->balance + (int) $lockedTransaction->amount;
            $lockedCourier->save();

            return (int) $lockedCourier->balance;
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
            'transaction_id' => $transactionId,
        ], 200);
    }

    private function transactionPayload(CourierTransaction $transaction): array
    {
        $category = $transaction->category ?: 'withdrawal';
        $type = $transaction->type ?: ($category === 'withdrawal' ? 'expense' : 'income');

        return [
            'id' => $transaction->id,
            'courier_id' => $transaction->courier_id,
            'card' => $transaction->card,
            'type' => $type,
            'category' => $category,
            'order_id' => $transaction->order_id,
            'courier_order_id' => $transaction->courier_order_id,
            'courier_task_id' => $transaction->courier_task_id,
            'amount' => (int) $transaction->amount,
            'commissionPercent' => (int) ($transaction->commissionPercent ?? 0),
            'commissionPrice' => (int) ($transaction->commissionPrice ?? 0),
            'netAmount' => (int) ($transaction->netAmount ?? $transaction->amount),
            'status' => $transaction->status,
            'description' => $transaction->description ?: $this->defaultDescription($category, $transaction->order_id),
            'rejected_desc' => $transaction->rejected_desc,
            'created_at' => optional($transaction->created_at)->toIso8601String(),
            'updated_at' => optional($transaction->updated_at)->toIso8601String(),
        ];
    }

    private function defaultDescription(string $category, ?int $orderId): string
    {
        return match ($category) {
            'order_delivery' => $orderId ? "Buyurtma #{$orderId} uchun yetkazish daromadi" : 'Yetkazish daromadi',
            'hub_delivery' => $orderId ? "Buyurtma #{$orderId} hubgacha yetkazildi" : 'Hubgacha yetkazish daromadi',
            'reversal' => $orderId ? "Buyurtma #{$orderId} bo‘yicha qaytarish" : 'Balans tuzatish',
            'penalty' => $orderId ? "Buyurtma #{$orderId} bo‘yicha jarima" : 'Jarima',
            default => "Balansdan yechib olish so'rovi",
        };
    }
}
