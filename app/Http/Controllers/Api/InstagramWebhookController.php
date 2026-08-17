<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InstagramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InstagramWebhookController extends Controller
{
    protected InstagramBotService $botService;

    public function __construct(InstagramBotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * Meta Webhook Verification Handshake (GET /api/instagram/webhook)
     */
    public function verify(Request $request)
    {
        return $this->botService->verifyWebhook($request);
    }

    /**
     * Handle incoming Meta Instagram Events (POST /api/instagram/webhook)
     */
    public function handle(Request $request)
    {
        // XAVFSIZLIK: endi to'g'ridan-to'g'ri $request->all() emas, balki
        // butun $request obyekti uzatiladi — chunki imzoni (X-Hub-Signature-256)
        // tekshirish uchun RAW body (getContent()) kerak, u $request->all()
        // orqali qayta tiklab bo'lmaydi. Tekshiruvning o'zi
        // InstagramBotService::handlePayload() ichida amalga oshiriladi.
        return $this->botService->handlePayload($request);
    }

    /**
     * Diagnostic Test Endpoint (GET /api/instagram/test)
     *
     * XAVFSIZLIK: bu endpoint ilgari HECH QANDAY autentifikatsiyasiz
     * (auth/token talab qilinmasdan) ochiq edi va javobda HAQIQIY,
     * to'liq Page Access Token qiymatlarini (config('services.instagram
     * .page_access_token') orqali, hattoki so'rovchi hech narsa
     * yubormasa ham) qaytarardi — ya'ni istalgan kishi shunchaki shu
     * URL'ni ochib, botning haqiqiy Instagram/Facebook kirish
     * tokenlarini (jumladan har bir bog'langan Page'ning access_token'ini)
     * ko'rib olishi mumkin edi. Bundan tashqari so'rovchi o'zining ?token=
     * parametrini yuborib, serverni ISTALGAN token bilan Facebook Graph
     * API'ga so'rov yuboruvchi ochiq vositaga aylantira olardi.
     *
     * Endi: (1) so'rovda maxfiy sarlavha (X-Admin-Secret) talab qilinadi,
     * bu qiymat faqat .env'dagi INSTAGRAM_DIAGNOSTIC_SECRET bilan mos
     * kelsagina davom etadi; (2) tashqi ?token= parametri ENDI e'tiborga
     * olinmaydi — doim serverning o'zidagi haqiqiy tokendan foydalaniladi;
     * (3) javobda hech qachon to'liq token qaytarilmaydi — faqat
     * boshlanishi (prefiks) va uzunligi ko'rsatiladi.
     */
    public function test(Request $request)
    {
        $adminSecret = config('services.instagram.diagnostic_secret', env('INSTAGRAM_DIAGNOSTIC_SECRET', ''));

        if (empty($adminSecret) || !hash_equals($adminSecret, (string) $request->header('X-Admin-Secret', ''))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $token = config('services.instagram.page_access_token', env('INSTAGRAM_PAGE_ACCESS_TOKEN'));

        if (!$token) {
            return response()->json([
                'error' => 'INSTAGRAM_PAGE_ACCESS_TOKEN is missing or empty in .env!',
                'token_configured' => false
            ], 400);
        }

        // 1. Check token validity
        $userResponse = Http::get("https://graph.facebook.com/v19.0/me?access_token={$token}");

        // 2. Discover linked Facebook Pages and Page Access Tokens
        $pagesResponse = Http::get("https://graph.facebook.com/v19.0/me/accounts?fields=id,name,access_token,instagram_business_account&access_token={$token}");

        return response()->json([
            'token_configured' => true,
            'token_prefix'     => substr($token, 0, 8) . '...',
            'token_length'     => strlen($token),
            'user_info'        => $userResponse->json(),
            'managed_pages'    => $this->maskPageTokens($pagesResponse->json()),
        ]);
    }

    /**
     * managed_pages ro'yxatidagi har bir Page'ning access_token maydonini
     * niqoblaydi — diagnostika uchun to'liq token qiymati shart emas,
     * faqat mavjud/mavjud emasligini bilish kifoya.
     */
    protected function maskPageTokens(?array $pagesResponse): ?array
    {
        if (!is_array($pagesResponse) || empty($pagesResponse['data']) || !is_array($pagesResponse['data'])) {
            return $pagesResponse;
        }

        foreach ($pagesResponse['data'] as &$page) {
            if (!empty($page['access_token'])) {
                $page['access_token'] = substr($page['access_token'], 0, 8) . '... (' . strlen($page['access_token']) . ' ta belgi)';
            }
        }
        unset($page);

        return $pagesResponse;
    }
}
