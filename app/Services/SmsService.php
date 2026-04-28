<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsService
{
    public function send(string $phone, string $message): array
    {
        $phone = preg_replace('/\D+/', '', $phone);

        if (empty($phone) || empty(trim($message))) {
            throw new RuntimeException('SMS yuborish uchun telefon raqami yoki matn bo‘sh.');
        }

        try {
            $response = $this->sendRequest($this->getToken(), $phone, $message);

            if ($response->status() === 401) {
                Cache::forget('eskiz_api_token');
                $response = $this->sendRequest($this->getToken(), $phone, $message);
            }

            if (!$response->successful()) {
                Log::error('Eskiz SMS yuborishda xatolik', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                throw new RuntimeException('SMS yuborilmadi. Iltimos, keyinroq qayta urunib ko\'ring.');
            }

            $payload = $response->json();

            Log::info('Eskiz SMS yuborildi', [
                'phone' => $phone,
                'response' => $payload,
            ]);

            return is_array($payload) ? $payload : ['status' => 'success'];
        } catch (ConnectionException $e) {
            Log::error('Eskiz SMS ulanish xatosi', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('SMS servisi bilan bog‘lanib bo‘lmadi. Iltimos, keyinroq qayta urinib ko‘ring.');
        } catch (\Throwable $e) {
            Log::error('Eskiz SMS umumiy xatolik', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw $e instanceof RuntimeException
                ? $e
                : new RuntimeException('SMS yuborishda kutilmagan xatolik yuz berdi.');
        }
    }

    private function sendRequest(string $token, string $phone, string $message)
    {
        return Http::asForm()
            ->acceptJson()
            ->withToken($token)
            ->connectTimeout((int) config('services.eskiz.connect_timeout', 5))
            ->timeout((int) config('services.eskiz.timeout', 10))
            ->post(config('services.eskiz.sms_url', 'https://notify.eskiz.uz/api/message/sms/send'), [
                'mobile_phone' => $phone,
                'message' => $message,
                'from' => config('services.eskiz.from', '4546'),
                'callback_url' => config('services.eskiz.callback_url'),
            ]);
    }

    private function getToken(): string
    {
        $token = Cache::get('eskiz_api_token');

        if ($token) {
            return $token;
        }

        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout((int) config('services.eskiz.connect_timeout', 5))
            ->timeout((int) config('services.eskiz.timeout', 10))
            ->post(config('services.eskiz.auth_url', 'https://notify.eskiz.uz/api/auth/login'), [
                'email' => config('services.eskiz.email'),
                'password' => config('services.eskiz.password'),
            ]);

        if (!$response->successful()) {
            Log::error('Eskiz token olishda xatolik', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new RuntimeException('SMS servis tokenini olishning imkoni bo‘lmadi.');
        }

        $token = data_get($response->json(), 'data.token');

        if (!$token) {
            throw new RuntimeException('SMS servis tokeni bo‘sh qaytdi.');
        }

        Cache::put('eskiz_api_token', $token, now()->addDays(29));

        return $token;
    }
}
