<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\YandexGeocoderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manzil xizmati — mijoz, do'kon ilovalari va boshqaruv uchun umumiy.
 * Yandex kaliti serverda qoladi (YandexGeocoderService).
 *
 * Javob har doim 200: `success=false` — manzil topilmadi yoki xizmat
 * vaqtincha ishlamadi. Xarita har siljitilganda so'raladi — ilova xato
 * oynasini ko'rsatmasdan "manzil aniqlanmadi" holatiga o'tadi.
 */
class GeocodeController extends Controller
{
    public function __construct(private readonly YandexGeocoderService $geocoder)
    {
    }

    public function reverse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
            'lang' => ['nullable', 'string', 'max:5'],
        ]);

        $result = $this->geocoder->reverse((float) $data['lat'], (float) $data['lon'], $this->lang($request));

        return response()->json([
            'success' => $result !== null,
            'data' => $result,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'lang' => ['nullable', 'string', 'max:5'],
        ]);

        $results = $this->geocoder->search($data['q'], $this->lang($request), (int) ($data['limit'] ?? 5));

        return response()->json([
            'success' => $results !== null,
            'data' => $results ?? [],
        ]);
    }

    private function lang(Request $request): string
    {
        return (string) ($request->input('lang') ?: $request->header('X-App-Locale') ?: app()->getLocale() ?: 'uz');
    }
}
