<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PremiumController extends Controller
{
    
    public function plans(Request $request)
    {
        $plans = [
            [
                'id'           => 'monthly',
                'price'        => 29000,
                'period'       => '1',
                'is_popular'   => false,
            ],
            [
                'id'           => 'quarterly',
                'price'        => 70000,
                'period'       => '3',
                'is_popular'   => false,
            ],
            [
                'id'           => 'yearly',
                'price'        => 250000,
                'period'       => '12',
                'is_popular'   => true,
            ],
        ];
        return response()->json([
            'ok'   => true,
            'data' => [
                'plans' => $plans,
                'currency' => 'uzs',
            ]
        ]);
    }
    public function status(Request $request)
    {
        $user = Auth::guard('user')->user();

        $isPremium = $user->isPremium();
        $expiry = $user->premium_expires_at?->format('Y-m-d H:i:s');
        $cashback = $user->cashback_rate ?? 1.00;

        return response()->json([
            'ok' => true,
            'data' => [
                'is_premium'     => $isPremium,
                'expiry_date'    => $expiry,
                'cashback_rate'  => $cashback,
            ]
        ]);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:monthly,quarterly,yearly'
        ]);

        $plan = $request->input('plan');
        $user = Auth::guard('user')->user();

        // Narxlar (real loyihada config yoki DB dan olish yaxshi)
        $prices = [
            'monthly'   => 29000,
            'quarterly' => 75000,
            'yearly'    => 250000,
        ];

        $months = [
            'monthly'   => 1,
            'quarterly' => 3,
            'yearly'    => 12,
        ];

        $amount = $prices[$plan];
        $durationMonths = $months[$plan];

        // Bu yerda to'lov tizimi integratsiyasi bo'lishi kerak
        // Masalan Payme, Click, Stripe, Uzum ...
        // Hozircha oddiy muvaffaqiyatli deb faraz qilamiz va payment_url qaytaramiz

        // Real loyihada:
        // $payment = Payme::createInvoice($amount, $user, $plan);
        // $paymentUrl = $payment->redirectUrl;

        // Test uchun:
        $paymentUrl = "https://your-payment-page.com/pay?user={$user->id}&plan=$plan&amount=$amount";

        // Vaqtinchalik subscription yaratish (to'lov tasdiqlangandan keyin faollashtiriladi)
        DB::transaction(function () use ($user, $plan, $durationMonths) {
            // Eski active subscriptionlarni to'xtatish mumkin (ixtiyoriy)
            $user->activeSubscription()?->update(['status' => 'cancelled']);

            Subscription::create([
                'user_id'     => $user->id,
                'plan'        => $plan,
                'starts_at'   => now(),
                'expires_at'  => now()->addMonths($durationMonths),
                'status'      => 'pending',   // to'lovdan keyin active qilinadi
            ]);
        });

        return response()->json([
            'ok' => true,
            'data' => [
                'payment_url' => $paymentUrl,
                'plan'        => $plan,
                'amount'      => $amount,
            ]
        ]);
    }

    // To'lov muvaffaqiyatli bo'lgandan keyin chaqiriladigan endpoint (webhook yoki callback)
    public function confirmPayment(Request $request)
    {
        // Bu endpointni to'lov provayderi chaqiradi yoki frontenddan post qilinadi
        // Xavfsizlik uchun token, signature tekshirish kerak!

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan'    => 'required|in:monthly,quarterly,yearly',
            'tx_id'   => 'required',   // transaction id
        ]);

        $user = Auth::guard('user')->user(); 

        $months = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12];
        $duration = $months[$request->plan] ?? 1;

        DB::transaction(function () use ($user, $request, $duration) {
            $user->update([
                'is_premium'         => true,
                'premium_expires_at' => now()->addMonths($duration),
            ]);

            $sub = $user->subscriptions()
                ->where('plan', $request->plan)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($sub) {
                $sub->update([
                    'status'                  => 'active',
                    'payment_transaction_id'  => $request->tx_id,
                    'expires_at'              => now()->addMonths($duration),
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function cancel(Request $request)
    {
        $user = Auth::guard('user')->user();

        $user->update([
            'is_premium'         => false,
            'premium_expires_at' => null,
        ]);

        $user->activeSubscription()?->update(['status' => 'cancelled']);

        return response()->json(['ok' => true, 'message' => 'Obuna bekor qilindi']);
    }
}