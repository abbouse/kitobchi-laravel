<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Support\Facades\Cache;

/**
 * Web (Nuxt) frontend uchun huquqiy hujjatlar (Policy) — o'qish uchun,
 * ochiq (auth talab qilmaydi) API. Admin boshqaruv panelida (A122\PolicyController)
 * yaratilgan/tahrirlangan yozuvlarni chiqaradi — kontent bu yerda FABRIKATSIYA
 * qilinmaydi, faqat mavjud (is_active=true) Policy'lar qaytariladi.
 *
 * GET /v1/kitobchi/legal            — barcha faol hujjatlar ro'yxati (qisqa)
 * GET /v1/kitobchi/legal/{slug}     — bitta hujjat (to'liq HTML kontent bilan)
 */
class LegalController extends Controller
{
    public function index()
    {
        $policies = Cache::remember('api:legal:index:v1', now()->addMinutes(10), function () {
            return Policy::active()
                ->with('translations')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (Policy $p) => [
                    'slug' => $p->slug,
                    'title' => $p->localizedTitle(),
                ])
                ->values();
        });

        return response()->json([
            'status' => 'success',
            'data' => $policies,
        ]);
    }

    public function show(string $slug)
    {
        $policy = Policy::active()->with('translations')->where('slug', $slug)->first();

        if (! $policy) {
            return response()->json([
                'status' => 'error',
                'message' => 'Topilmadi',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'slug' => $policy->slug,
                'title' => $policy->localizedTitle(),
                'content' => $policy->localizedContent(),
                'updated_at' => optional($policy->updated_at)->toIso8601String(),
            ],
        ]);
    }
}
