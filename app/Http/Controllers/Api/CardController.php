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

    private function success(array $payload = [], int $status = 200)
    {
        return response()->json(array_merge([
            'status' => 'success',
            'ok' => true,
        ], $payload), $status);
    }

    private function error(string $message, int $status = 400, array $extra = [])
    {
        return response()->json(array_merge([
            'status' => 'error',
            'ok' => false,
            'message' => $message,
            'error' => $message,
        ], $extra), $status);
    }

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
            'expire' => 'required|string|size:4',
        ]);

        $user = $request->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $response = $this->payme->request('cards.create', [
            'card' => [
                'number' => $request->number,
                'expire' => $request->expire,
            ],
            'save' => true,
        ]);

        if (isset($response['error'])) {
            return $this->error(
                $response['error']['message'] ?? 'Payme xatoligi',
                400,
                [
                'error_code' => $response['error']['code'] ?? null
                ]
            );
        }

        $cardData = $response['result']['card'];

        $card = UserCard::updateOrCreate(
            ['card_number' => $cardData['number'], 'user_id' => $user->id],
            [
                'payme_token' => $cardData['token'],
                'is_verified' => false,
            ]
        );

        $verifyResponse = $this->payme->request('cards.get_verify_code', [
            'token' => $cardData['token']
        ]);

        if (isset($verifyResponse['error'])) {
            return $this->error(
                'SMS yuborishda xatolik: ' . ($verifyResponse['error']['message'] ?? ''),
                400
            );
        }

        return $this->success([
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
            'code'  => 'required|string|size:6',
        ]);

        $response = $this->payme->request('cards.verify', [
            'token' => $request->token,
            'code'  => $request->code,
        ]);

        if (isset($response['error'])) {
            return $this->error(
                'Tasdiqlash xatosi: ' . ($response['error']['message'] ?? 'Kod noto‘g‘ri'),
                400
            );
        }

        UserCard::where('payme_token', $request->token)
            ->update(['is_verified' => true]);

        return $this->success([
            'message' => 'Karta muvaffaqiyatli tasdiqlandi va bog‘landi',
            'card' => $response['result']['card']
        ]);
    }

    /**
     * 3. Foydalanuvchining bog'langan kartalarini ko'rish
     */
    public function index(Request $request)
    {
        if (!$request->user()) {
            return $this->error('Unauthorized', 401);
        }

        $cards = $request->user()->cards()
            ->where('is_verified', true)
            ->get(['id', 'card_number', 'created_at']);

        return $this->success([
            'data' => $cards
        ]);
    }

    /**
     * 4. Kartani o'chirish
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()) {
            return $this->error('Unauthorized', 401);
        }

        $card = $request->user()->cards()->findOrFail($id);

        // Payme-dan ham tokenni o'chirish (ixtiyoriy, lekin tavsiya etiladi)
        $this->payme->request('cards.remove', [
            'token' => $card->payme_token
        ]);

        $card->delete();

        return $this->success([
            'message' => 'Karta o‘chirildi'
        ]);
    }
}
