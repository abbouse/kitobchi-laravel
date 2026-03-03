<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserCard;
use App\Services\PaymeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardController extends Controller
{
    protected $payme;

    public function __construct(PaymeService $payme)
    {
        $this->payme = $payme;
    }

    /**
     * 1. Karta yaratish va SMS yuborish
     */
    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|string|size:16',
            'expire' => 'required|string|size:4', // MMYY formatida, masalan: 0528
        ]);

        $user = $request->user();

        // Payme-ga karta yaratish so'rovini yuborish
        $response = $this->payme->request('cards.create', [
            'card' => [
                'number' => $request->number,
                'expire' => $request->expire,
            ],
            'save' => true, // Recurrent to'lovlar uchun tokenni saqlash
        ]);

        if (isset($response['error'])) {
            return response()->json([
                'ok' => false,
                'message' => $response['error']['message'] ?? 'Payme xatoligi',
                'error_code' => $response['error']['code'] ?? null
            ], 400);
        }

        $cardData = $response['result']['card'];

        // Bazada ushbu karta mavjudligini tekshiramiz (agar avval qo'shilgan bo'lsa)
        // Tokenni yangilab qo'yamiz yoki yangi karta ochamiz
        $card = UserCard::updateOrCreate(
            ['card_number' => $cardData['number'], 'user_id' => $user->id],
            [
                'payme_token' => $cardData['token'],
                'is_verified' => false, // Hali SMS tasdiqlanmagan
            ]
        );

        // Karta yaratilgach, darhol SMS kod yuboramiz
        $verifyResponse = $this->payme->request('cards.get_verify_code', [
            'token' => $cardData['token']
        ]);

        if (isset($verifyResponse['error'])) {
            return response()->json([
                'ok' => false,
                'message' => 'SMS yuborishda xatolik: ' . ($verifyResponse['error']['message'] ?? ''),
            ], 400);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Karta yaratildi va SMS kod yuborildi',
            'data' => [
                'token' => $cardData['token'],
                'number' => $cardData['number']
            ]
        ]);
    }

    /**
     * 2. SMS kodni tasdiqlash
     */
    public function verify(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'code'  => 'required|string|size:6', // Payme test kodlari odatda 6 xonali
        ]);

        $response = $this->payme->request('cards.verify', [
            'token' => $request->token,
            'code'  => $request->code,
        ]);

        if (isset($response['error'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Tasdiqlash xatosi: ' . ($response['error']['message'] ?? 'Kod noto‘g‘ri'),
            ], 400);
        }

        // Karta muvaffaqiyatli tasdiqlandi
        UserCard::where('payme_token', $request->token)
            ->update(['is_verified' => true]);

        return response()->json([
            'ok' => true,
            'message' => 'Karta muvaffaqiyatli tasdiqlandi va bog‘landi',
            'card' => $response['result']['card']
        ]);
    }

    /**
     * 3. Foydalanuvchining bog'langan kartalarini ko'rish
     */
    public function index(Request $request)
    {
        $cards = $request->user()->cards()
            ->where('is_verified', true)
            ->get(['id', 'card_number', 'created_at']);

        return response()->json([
            'ok' => true,
            'data' => $cards
        ]);
    }

    /**
     * 4. Kartani o'chirish
     */
    public function destroy(Request $request, $id)
    {
        $card = $request->user()->cards()->findOrFail($id);

        // Payme-dan ham tokenni o'chirish (ixtiyoriy, lekin tavsiya etiladi)
        $this->payme->request('cards.remove', [
            'token' => $card->payme_token
        ]);

        $card->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Karta o‘chirildi'
        ]);
    }
}