<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReadingIntelligence\ReadingIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * "Bu menga mosmi?" kartochkasi — item sahifasida rating tagida ko'rinadi.
 * Guest ham, login qilgan foydalanuvchi ham chaqira oladi (auth ixtiyoriy —
 * ProductsController dagi boshqa ochiq endpointlar bilan bir xil naqsh).
 * Til `SetApiLocale` middleware orqali allaqachon aniqlangan (App::getLocale()).
 */
class ReadingIntelligenceController extends Controller
{
    public function __construct(
        private readonly ReadingIntelligenceService $readingIntelligence,
    ) {
    }

    public function show(Request $request, string $type, int $id)
    {
        $type = strtolower($type);
        if (! in_array($type, ['book', 'stationery'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid product type',
            ], 422);
        }

        $user = $request->user('user') ?? auth('sanctum')->user() ?? Auth::guard('user')->user();
        $locale = App::getLocale();

        $payload = $this->readingIntelligence->forProduct($type, $id, $user, $locale);

        if ($payload === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }
}
