<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Services\SellerPremiumService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PremiumController extends Controller
{
    public function __construct(private readonly SellerPremiumService $premiumService)
    {
        $this->middleware('auth:seller');
    }

    public function info(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $storeSeller = $this->premiumService->resolveStoreSeller($seller);

        return response()->json([
            'success' => true,
            'data' => $this->premiumService->syncSeller($storeSeller),
        ]);
    }

    public function buy(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:monthly,quarterly,yearly',
        ]);

        $seller = Auth::guard('seller')->user();
        $storeSeller = $this->premiumService->resolveStoreSeller($seller);
        $result = $this->premiumService->buy($storeSeller, (string) $request->plan);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'new_balance' => $result['data']['balance'] ?? (int) $storeSeller->balance,
            'premium_expires_at' => $result['data']['premium_expires_at'] ?? null,
            'data' => $result['data'],
        ], $result['success'] ? 200 : 422);
    }

    public function cancel(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $storeSeller = $this->premiumService->resolveStoreSeller($seller);
        $result = $this->premiumService->cancelAtPeriodEnd($storeSeller);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'],
        ], $result['success'] ? 200 : 422);
    }
}
