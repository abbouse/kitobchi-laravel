<?php

namespace App\Services\KitobchiId;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Kitobchi ID kirish kodi.
 *
 * Yetkazish tartibi:
 *  1. Raqam Kitobchi'da mavjud va Kitobchi ilovasi faol bo'lsa — kod ilovadagi rasmiy
 *     chatga + push (SMS yuborilmaydi, pul tejaladi);
 *  2. aks holda, yoki foydalanuvchi `sms_fallback_seconds` dan keyin so'rasa — SMS
 *     (xuddi o'sha kod).
 *
 * Kod keshda HMAC (tekshirish uchun) va shifrlangan (SMS zaxirasi uchun) holda turadi;
 * `users.verifyCode` ga tegilmaydi. Master/test kod YO'Q.
 */
class OtpService
{
    public function __construct(private SmsService $sms, private AppCodeChannel $app)
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
     * @param  bool  $viaSms  foydalanuvchi "SMS orqali olish" ni bosdi
     * @return array{ok:bool, channel?:string, error?:string, retry_after?:int, sms_after?:int}
     */
    public function send(string $phone, string $clientName, bool $viaSms = false): array
    {
        $key = self::key($phone);
        $existing = Cache::get($key);
        $name = self::cleanName($clientName);

        // Ilovaga yuborilgan faol kodni SMS bilan ham yuborish (o'sha kod)
        if ($viaSms && is_array($existing) && now()->getTimestamp() < (int) $existing['expires'] && ($existing['channel'] ?? '') === 'app') {
            $wait = (int) config('kitobchi_id.sms_fallback_seconds', 30) - (now()->getTimestamp() - (int) $existing['sent_at']);
            if ($wait > 0) {
                return ['ok' => false, 'error' => 'wait', 'retry_after' => $wait];
            }
            if (! $this->underDailyLimit($phone)) {
                return ['ok' => false, 'error' => 'daily_limit'];
            }
            try {
                $code = Crypt::decryptString((string) $existing['enc']);
            } catch (Throwable) {
                $code = null;
            }
            if ($code !== null) {
                if (! $this->sendSms($phone, $code, $name)) {
                    return ['ok' => false, 'error' => 'sms_failed'];
                }
                $existing['channel'] = 'sms';
                $existing['sent_at'] = now()->getTimestamp();
                Cache::put($key, $existing, max(1, (int) $existing['expires'] - now()->getTimestamp()));
                $this->countDaily($phone);

                return ['ok' => true, 'channel' => 'sms'];
            }
        }

        $resend = (int) config('kitobchi_id.otp_resend_seconds', 60);
        if (is_array($existing) && (now()->getTimestamp() - (int) $existing['sent_at']) < $resend) {
            return [
                'ok' => false, 'error' => 'wait',
                'retry_after' => $resend - (now()->getTimestamp() - (int) $existing['sent_at']),
                'channel' => $existing['channel'] ?? 'sms',
            ];
        }
        if (! $this->underDailyLimit($phone)) {
            return ['ok' => false, 'error' => 'daily_limit'];
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $ttl = (int) config('kitobchi_id.otp_ttl', 300);
        $channel = null;
        $inChat = false;
        $user = $viaSms ? null : $this->existingUser($phone);

        if ($user) {
            $delivered = $this->app->deliver($user, $code, $name, $ttl);
            if ($delivered === AppCodeChannel::DELIVERED_PUSH) {
                $channel = 'app';
            }
            $inChat = $delivered !== null;
        }
        if ($channel === null) {
            if ($this->sendSms($phone, $code, $name)) {
                $channel = 'sms';
            } elseif ($inChat) {
                // Push ham, SMS ham yetmadi — lekin kod Kitobchi ilovasidagi chatda turibdi.
                // Xato qaytarsak chatdagi kod "ishlamaydigan" bo'lib qolardi.
                Log::warning('kitobchi-id: push and sms failed, code is only in app chat');
                $channel = 'app';
            } else {
                return ['ok' => false, 'error' => 'sms_failed'];
            }
        }

        Cache::put($key, [
            'hash' => $this->hmac($phone, $code),
            'enc' => Crypt::encryptString($code),
            'channel' => $channel,
            'sent_at' => now()->getTimestamp(),
            'expires' => now()->getTimestamp() + $ttl,
            'attempts' => 0,
        ], $ttl);
        $this->countDaily($phone);

        return $channel === 'app'
            ? ['ok' => true, 'channel' => 'app', 'sms_after' => (int) config('kitobchi_id.sms_fallback_seconds', 30)]
            : ['ok' => true, 'channel' => 'sms'];
    }

    /** Joriy kod qaysi kanal orqali yuborilgan (sahifada to'g'ri matn ko'rsatish uchun). */
    public function status(string $phone): ?array
    {
        $data = Cache::get(self::key($phone));
        if (! is_array($data) || now()->getTimestamp() > (int) $data['expires']) {
            return null;
        }

        return [
            'channel' => $data['channel'] ?? 'sms',
            'sms_after' => max(0, (int) config('kitobchi_id.sms_fallback_seconds', 30) - (now()->getTimestamp() - (int) $data['sent_at'])),
        ];
    }

    /** @return 'ok'|'invalid'|'expired'|'locked' */
    public function verify(string $phone, string $code): string
    {
        $key = self::key($phone);
        $data = Cache::get($key);
        if (! is_array($data) || now()->getTimestamp() > (int) $data['expires']) {
            return 'expired';
        }

        $code = preg_replace('/\D+/', '', $code);
        if (strlen($code) === 6 && hash_equals($data['hash'], $this->hmac($phone, $code))) {
            Cache::forget($key);
            if ($user = $this->existingUser($phone)) {
                $this->app->markUsed((int) $user->id); // chatdagi kod "ishlatildi" bo'ladi
            }

            return 'ok';
        }

        $data['attempts'] = (int) $data['attempts'] + 1;
        if ($data['attempts'] >= (int) config('kitobchi_id.otp_max_attempts', 5)) {
            Cache::forget($key);
            if ($user = $this->existingUser($phone)) {
                $this->app->markUsed((int) $user->id);
            }

            return 'locked';
        }
        Cache::put($key, $data, max(1, (int) $data['expires'] - now()->getTimestamp()));

        return 'invalid';
    }

    private function existingUser(string $phone): ?User
    {
        $user = User::where('phone_number', $phone)->where('isDeleted', 'no')->first();

        return $user && ! $user->isBlocked() ? $user : null;
    }

    private function sendSms(string $phone, string $code, string $name): bool
    {
        try {
            // Ilova nomi SMS'da ko'rsatiladi — foydalanuvchi kod qayerga kirish uchun
            // so'ralayotganini biladi (fishingdan himoya).
            $this->sms->send($phone, "<#> Kitobchi ID kodi: {$code}. \"{$name}\" ilovasiga kirish uchun. Kodni hech kimga bermang.");

            return true;
        } catch (Throwable $e) {
            Log::warning('kitobchi-id: sms failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function underDailyLimit(string $phone): bool
    {
        return (int) Cache::get(self::dayKey($phone), 0) < (int) config('kitobchi_id.otp_daily_limit', 10);
    }

    private function countDaily(string $phone): void
    {
        Cache::put(self::dayKey($phone), (int) Cache::get(self::dayKey($phone), 0) + 1, 86400);
    }

    private static function key(string $phone): string
    {
        return 'kid_otp:'.$phone;
    }

    private static function dayKey(string $phone): string
    {
        return 'kid_otp_day:'.$phone.':'.date('Ymd');
    }

    public static function cleanName(string $clientName): string
    {
        return mb_substr(preg_replace("/[^\p{L}\p{N} .\-'’ʻʼ]/u", '', $clientName), 0, 24);
    }

    private function hmac(string $phone, string $code): string
    {
        return hash_hmac('sha256', $phone.'|'.$code, (string) config('app.key'));
    }
}
