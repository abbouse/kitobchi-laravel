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

        // MUHIM: `forProduct()` `null` qaytarishi ikki xil (lekin front-end
        // uchun bir xil natijali) holatni bildiradi — (1) mahsulot umuman
        // topilmadi, yoki (2) mahsulot bor, lekin AI hali tahlil qilmagan
        // (shu bois karta hali ko'rsatilmaydi). Ikkalasi ham XATO EMAS —
        // oddiy "hozircha ma'lumot yo'q" holati, shuning uchun 404 o'rniga
        // muvaffaqiyatli javob + `data: null` qaytaramiz. Bu, ayniqsa,
        // server monitoring/xato darajasini yangi qo'shilgan (hali AI
        // yetib bormagan) mahsulotlar bilan keraksiz "xato" sifatida
        // to'ldirib yubormasligi uchun muhim.
        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }
}
