<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PremiumController extends Controller
{
    private function success(array $payload = [], int $status = 200)
    {
        return response()->json(array_merge([
            'status' => 'success',
            'ok' => true,
        ], $payload), $status);
    }

    private function error(string $message, int $status = 400, array $extra = [])
    {
        return response()->json(array_merge([
            'status' => 'error',
            'ok' => false,
            'message' => $message,
            'error' => $message,
        ], $extra), $status);
    }
    
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
                'price'        => 75000,
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
        return $this->success([
            'data' => [
                'plans' => $plans,
                'currency' => 'uzs',
            ]
        ]);
    }

    public function status(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $isPremium = $user->isPremium();
        $expiry = $user->premium_expires_at?->format('Y-m-d H:i:s');
        $cashback = $user->cashback_rate ?? 1.00;

        return $this->success([
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
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

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
        $paymentUrl = "https://your-payment-page.com/pay?user={$user->id}&plan=$plan&amount=$amount";

        DB::transaction(function () use ($user, $plan, $durationMonths) {
            $user->activeSubscription()?->update(['status' => 'cancelled']);

            Subscription::create([
                'user_id'     => $user->id,
                'plan'        => $plan,
                'starts_at'   => now(),
                'expires_at'  => now()->addMonths($durationMonths),
                'status'      => 'pending',
            ]);
        });

        return $this->success([
            'data' => [
                'payment_url' => $paymentUrl,
                'plan'        => $plan,
                'amount'      => $amount,
            ]
        ]);
    }

    public function confirmPayment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan'    => 'required|in:monthly,quarterly,yearly',
            'tx_id'   => 'required',
        ]);

        $user = Auth::guard('user')->user(); 
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

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

        return $this->success();
    }

    public function cancel(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $user->update([
            'is_premium'         => false,
            'premium_expires_at' => null,
        ]);

        $user->activeSubscription()?->update(['status' => 'cancelled']);

        return $this->success(['message' => 'Obuna bekor qilindi']);
    }
}
