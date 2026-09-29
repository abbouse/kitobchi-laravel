<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = max(4, min(20, (int) $request->query('limit', 8)));
        // lite=1: yangi bosh sahifa (home/layout) kitob va do'konlarni o'zi oladi —
        // bu yerda faqat hisoblagichlar va bannerlar qaytadi, do'konlar oldindan yuklanmaydi
        $lite = $request->boolean('lite');
        $user = auth('user')->user();
        if (!$user) {
            return \Illuminate\Support\Facades\Cache::remember("api_home_guest_{$limit}".($lite ? '_lite' : ''), 60, function () use ($request, $limit, $lite) {
                $errors = [];
                $countsPayload = $this->section('counts', fn () => app(UserController::class)->getGlobalCounts($request), $errors);
                $newsPayload = $this->section('news', fn () => app(NewsController::class)->index($request), $errors);
                $newProductsPayload = $lite ? [] : $this->section('new_products', fn () => app(ProductsController::class)->index($request, (string) $limit), $errors);
                $recommendedPayload = $lite ? [] : $this->section('recommended_products', fn () => app(ProductsController::class)->recommendation($request, (string) $limit), $errors);
                $sellersPayload = $lite ? [] : $this->section('sellers', fn () => app(ProductsController::class)->sellersWithLatestProducts($request), $errors);

                $news = collect($newsPayload['data'] ?? [])->values();

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'counts' => $countsPayload['data'] ?? null,
                        'news' => $news->all(),
                        'top_banners' => $news->where('type', 'top_banner')->values()->all(),
                        'center_banners' => $news->where('type', 'center_banner')->values()->all(),
                        'new_products' => $newProductsPayload['data'] ?? [],
                        'recommended_products' => $recommendedPayload['data'] ?? [],
                        'recommendation_based_on' => $recommendedPayload['based_on'] ?? 'default',
                        'sellers' => $sellersPayload['data'] ?? [],
                    ],
                    'errors' => $errors,
                    'meta' => [
                        'limit' => $limit,
                        'generated_at' => now()->toIso8601String(),
                    ],
                ]);
            });
        }

        $errors = [];

        $countsPayload = $this->section('counts', fn () => app(UserController::class)->getGlobalCounts($request), $errors);

        if (($countsPayload['status'] ?? null) === 'error' && ($countsPayload['error_code'] ?? null) === 'user_account_blocked') {
            return response()->json($countsPayload, 423);
        }

        $newsPayload = $this->section('news', fn () => app(NewsController::class)->index($request), $errors);
        $newProductsPayload = $lite ? [] : $this->section('new_products', fn () => app(ProductsController::class)->index($request, (string) $limit), $errors);
        $recommendedPayload = $lite ? [] : $this->section('recommended_products', fn () => app(ProductsController::class)->recommendation($request, (string) $limit), $errors);
        $sellersPayload = $lite ? [] : $this->section('sellers', fn () => app(ProductsController::class)->sellersWithLatestProducts($request), $errors);

        $news = collect($newsPayload['data'] ?? [])->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'counts' => $countsPayload['data'] ?? null,
                'news' => $news->all(),
                'top_banners' => $news->where('type', 'top_banner')->values()->all(),
                'center_banners' => $news->where('type', 'center_banner')->values()->all(),
                'new_products' => $newProductsPayload['data'] ?? [],
                'recommended_products' => $recommendedPayload['data'] ?? [],
                'recommendation_based_on' => $recommendedPayload['based_on'] ?? 'default',
                'sellers' => $sellersPayload['data'] ?? [],
            ],
            'errors' => $errors,
            'meta' => [
                'limit' => $limit,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function section(string $name, callable $callback, array &$errors): array
    {
        try {
            $response = $callback();
            $payload = $response instanceof JsonResponse
                ? (array) $response->getData(true)
                : (array) $response;

            if (($payload['status'] ?? null) !== 'success') {
                $errors[$name] = (string) ($payload['message'] ?? 'Section failed.');
            }

            return $payload;
        } catch (\Throwable $e) {
            Log::warning('Home aggregate section failed', [
                'section' => $name,
                'error' => $e->getMessage(),
            ]);

            $errors[$name] = 'Section failed.';

            return [
                'status' => 'error',
                'data' => [],
            ];
        }
    }
}
