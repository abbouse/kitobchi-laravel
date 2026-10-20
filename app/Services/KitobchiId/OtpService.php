<?php

namespace App\Services\KitobchiId;

use App\Services\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Kitobchi ID uchun SMS kod. Kod faqat HMAC ko'rinishida keshda saqlanadi,
 * `users.verifyCode` ustuniga tegmaydi (sayt/ilova logini bilan aralashmaydi).
 * Master/test kod YO'Q.
 */
class OtpService
{
    public function __construct(private SmsService $sms)
    {
    }

    public static function normalizePhone(string $raw): ?string
    {
        $phone = preg_replace('/\D+/', '', $raw);
        if (strlen($phone) === 9) {
            $phone = '998'.$phone;
        }

        return preg_match('/^998\d{9}$/', $phone) ? $phone : null;
    }

    /**
     * @return array{ok:bool, error?:string, retry_after?:int}
     */
    public function send(string $phone, string $clientName): array
    {
        $resend = (int) config('kitobchi_id.otp_resend_seconds', 60);
        $key = 'kid_otp:'.$phone;
        $existing = Cache::get($key);
        if (is_array($existing) && (time() - (int) $existing['sent_at']) < $resend) {
            return ['ok' => false, 'error' => 'wait', 'retry_after' => $resend - (time() - (int) $existing['sent_at'])];
        }

        $dayKey = 'kid_otp_day:'.$phone.':'.date('Ymd');
        $count = (int) Cache::get($dayKey, 0);
        if ($count >= (int) config('kitobchi_id.otp_daily_limit', 10)) {
            return ['ok' => false, 'error' => 'daily_limit'];
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $name = mb_substr(preg_replace("/[^\p{L}\p{N} .\-'’ʻʼ]/u", '', $clientName), 0, 24);

        try {
            // Ilova nomi SMS'da ko'rsatiladi — foydalanuvchi kod qayerga kirish uchun
            // so'ralayotganini biladi (fishingdan himoya).
            $this->sms->send($phone, "<#> Kitobchi ID kodi: {$code}. \"{$name}\" ilovasiga kirish uchun. Kodni hech kimga bermang.");
        } catch (Throwable $e) {
            Log::warning('kitobchi-id: sms failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'sms_failed'];
        }

        $ttl = (int) config('kitobchi_id.otp_ttl', 300);
        Cache::put($key, [
            'hash' => $this->hmac($phone, $code),
            'sent_at' => time(),
            'expires' => time() + $ttl,
            'attempts' => 0,
        ], $ttl);
        Cache::put($dayKey, $count + 1, 86400);

        return ['ok' => true];
    }

    /** @return 'ok'|'invalid'|'expired'|'locked' */
    public function verify(string $phone, string $code): string
    {
        $key = 'kid_otp:'.$phone;
        $data = Cache::get($key);
        if (! is_array($data) || time() > (int) $data['expires']) {
            return 'expired';
        }

        $code = preg_replace('/\D+/', '', $code);
        if (strlen($code) === 6 && hash_equals($data['hash'], $this->hmac($phone, $code))) {
            Cache::forget($key);

            return 'ok';
        }

        $data['attempts'] = (int) $data['attempts'] + 1;
        if ($data['attempts'] >= (int) config('kitobchi_id.otp_max_attempts', 5)) {
            Cache::forget($key);

            return 'locked';
        }
        Cache::put($key, $data, max(1, (int) $data['expires'] - time()));

        return 'invalid';
    }

    private function hmac(string $phone, string $code): string
    {
        return hash_hmac('sha256', $phone.'|'.$code, (string) config('app.key'));
    }
}
