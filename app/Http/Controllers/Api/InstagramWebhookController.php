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
     *
     * MUHIM (2026-08): bot Instagram Login oqimiga o'tkazilgani sabab bu
     * diagnostika endpointi ham graph.facebook.com/me+me/accounts (Page-based)
     * o'rniga graph.instagram.com/me (Instagram User token) ni tekshiradi.
     * Bundan tashqari, Meta'ning rasmiy webhook sozlash yo'riqnomasidagi
     * 3-qadam ("профессиональный аккаунт... включить получение уведомлений
     * путем выполнения вызова API") Dashboard'dagi "Webhooks" tugmachasi
     * BILAN AVTOMATIK bajarilmaydi — bu alohida, majburiy POST
     * /me/subscribed_apps chaqiruvi, va aynan shu qadam bajarilmagani bot
     * "sozlandi, lekin ishlamayapti" muammosining asosiy sababi bo'lishi
     * mumkin edi. Shu sabab bu endpoint endi har chaqirilganda akkauntni
     * "messages" (va bog'liq messaging_* field'lar)ga QAYTA obuna qiladi —
     * bu amal xavfsiz va idempotent (necha marta takrorlansa ham zarar
     * keltirmaydi), so'ng joriy holatni qaytarib ko'rsatadi.
     */
    public function test(Request $request)
    {
        $adminSecret = config('services.instagram.diagnostic_secret', env('INSTAGRAM_DIAGNOSTIC_SECRET', ''));

        if (empty($adminSecret) || !hash_equals($adminSecret, (string) $request->header('X-Admin-Secret', ''))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $token = config('services.instagram.access_token', env('INSTAGRAM_ACCESS_TOKEN'));

        if (!$token) {
            return response()->json([
                'error' => 'INSTAGRAM_ACCESS_TOKEN is missing or empty in .env!',
                'token_configured' => false,
            ], 400);
        }

        // 1. Token haqiqiy Instagram professional akkauntiga tegishli
        //    ekanini va uning ID/username'ini tekshirish.
        $accountResponse = Http::withToken($token)
            ->timeout(10)->connectTimeout(5)
            ->get('https://graph.instagram.com/v26.0/me', [
                'fields' => 'user_id,username,name,account_type',
            ]);

        // 2. Webhook obunasini (qayta) yoqish — Meta docs: "Отправка
        //    сообщений" bo'limida talab qilingan majburiy qadam.
        $subscribeFields = 'messages,messaging_postbacks,messaging_optins,messaging_seen,message_reactions,messaging_referral';
        $subscribeResponse = Http::withToken($token)
            ->timeout(10)->connectTimeout(5)
            ->post('https://graph.instagram.com/v26.0/me/subscribed_apps', [
                'subscribed_fields' => $subscribeFields,
            ]);

        // 3. Joriy obuna holatini ko'rsatish (subscribe chaqiruvidan keyin).
        $subscriptionsResponse = Http::withToken($token)
            ->timeout(10)->connectTimeout(5)
            ->get('https://graph.instagram.com/v26.0/me/subscribed_apps');

        return response()->json([
            'token_configured'          => true,
            'token_prefix'              => substr($token, 0, 8) . '...',
            'token_length'              => strlen($token),
            'account_info'              => $accountResponse->json(),
            'account_info_ok'           => $accountResponse->successful(),
            'subscribe_attempt'         => $subscribeResponse->json(),
            'subscribe_attempt_ok'      => $subscribeResponse->successful(),
            'webhook_subscriptions'     => $subscriptionsResponse->json(),
            'webhook_subscriptions_ok'  => $subscriptionsResponse->successful(),
        ]);
    }
}
