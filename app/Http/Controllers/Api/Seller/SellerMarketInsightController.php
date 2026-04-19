<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kangaroo seller intelligence — mahsulot bo'yicha ko'p bozor tahlili (Flutter uchun matnsiz signal kodlari).
 */
class SellerMarketInsightController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    private function getStoreSellerId($seller): int
    {
        return (int) ($seller->parent_id ?: $seller->id);
    }

    private function hasProductAccess($seller): bool
    {
        return ! $seller->parent_id || in_array((int) $seller->role, [1, 2], true);
    }

    public function show(Request $request, string $type, int $id)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $this->hasProductAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $type = strtolower($type);
        if (! in_array($type, ['book', 'stationery'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'type must be book or stationery',
            ], 422);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        if ($type === 'book') {
            $exists = Books::where('id', $id)->where('seller_id', $storeSellerId)->where('is_hidden', false)->exists();
        } else {
            $exists = Stationery::where('id', $id)->where('seller_id', $storeSellerId)->where('is_hidden', false)->exists();
        }

        if (! $exists) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $base = rtrim((string) config('services.kangaroo.url'), '/');
        $key = (string) config('services.kangaroo.key');
        if ($base === '' || $key === '') {
            Log::warning('Kangaroo API not configured (services.kangaroo.url / key)');

            return response()->json(['success' => false, 'message' => 'Market insight unavailable'], 503);
        }

        $markets = $request->query('markets');
        $query = [];
        if (is_string($markets) && $markets !== '') {
            $query['markets'] = $markets;
        }

        $url = $base.'/api/seller/'.$storeSellerId.'/product/'.$type.'/'.$id.'/insight';
        try {
            $response = Http::timeout(20)
                ->withHeaders(['X-Kangaroo-Key' => $key])
                ->acceptJson()
                ->get($url, $query);
        } catch (\Throwable $e) {
            Log::error('Kangaroo insight request failed', ['exception' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Upstream error'], 502);
        }

        if (! $response->successful()) {
            Log::warning('Kangaroo insight bad status', ['status' => $response->status(), 'body' => $response->body()]);

            return response()->json([
                'success' => false,
                'message' => 'Market insight failed',
                'upstream_status' => $response->status(),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);
    }

    /**
     * Bir xaridda ko'p sotilgan mahsulot juftlari (Kangaroo signal formatida).
     */
    public function bundleSignals(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $base = rtrim((string) config('services.kangaroo.url'), '/');
        $key = (string) config('services.kangaroo.key');
        if ($base === '' || $key === '') {
            return response()->json(['success' => false, 'message' => 'Market insight unavailable'], 503);
        }

        $limit = min(30, max(1, (int) $request->query('limit', 12)));
        $url = $base.'/api/seller/'.$storeSellerId.'/bundle-signals';

        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Kangaroo-Key' => $key])
                ->acceptJson()
                ->get($url, ['limit' => $limit]);
        } catch (\Throwable $e) {
            Log::error('Kangaroo bundle-signals failed', ['exception' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Upstream error'], 502);
        }

        if (! $response->successful()) {
            return response()->json(['success' => false, 'message' => 'Upstream failed'], 502);
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);
    }
}
