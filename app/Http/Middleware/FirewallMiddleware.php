<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FirewallMiddleware
{
    /**
     * Oq ro'yxat (hech qachon bloklanmaydigan IP lar)
     */
    protected array $whitelist = [
        '127.0.0.1',
        '::1',
        '0.0.0.0',
    ];

    /**
     * Qora ro'yxatdagi va shubhali skaner so'rovlarini real-vaqtda to'xtatish
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // 1. Oq ro'yxat tekshiruvi (Localhost va xavfsiz IP'lar)
        if (in_array($ip, $this->whitelist, true)) {
            return $next($request);
        }

        // 2. Bloklangan IP manzillar tekshiruvi (Kesh orqali tezkor)
        $blockedIps = Cache::remember('firewall_blocked_ips', 30, function () {
            $file = storage_path('app/security_blocked_ips.json');
            if (File::exists($file)) {
                $data = json_decode((string) File::get($file), true);
                return is_array($data) ? $data : [];
            }
            return [];
        });

        if (in_array($ip, $blockedIps, true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error'   => 'Kirish taqiqlangan',
                    'message' => 'Sizning IP manzilingiz xavfsizlik devori (Firewall) tomonidan cheklangan.',
                    'ip'      => $ip,
                ], 403);
            }

            return response(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>403 Forbidden</title>' .
                '<style>body{font-family:system-ui,sans-serif;background:#0d1117;color:#c9d1d9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}' .
                '.box{text-align:center;max-width:500px;padding:30px;background:#161b22;border:1px solid #30363d;border-radius:12px}' .
                'h1{color:#f85149;margin:0 0 10px;font-size:24px}p{color:#8b949e;line-height:1.5}</style></head>' .
                '<body><div class="box"><h1>403 · Kirish Cheklangan</h1>' .
                '<p>Ushbu IP manzil (' . htmlspecialchars($ip) . ') xavfsizlik devori tomonidan bloklangan.</p>' .
                '<p style="font-size:12px;color:#484f58">Kitobchi Security Firewall Guard</p></div></body></html>',
                403
            );
        }

        // 3. Avtomatik WAF Tuzog'i (Honeypot / Sensitive File Probing)
        // Agar xuruj qiluvchi to'g'ridan-to'g'ri .env, .git, phpmyadmin kabi yo'llarni so'rasa:
        $path = strtolower($request->path());
        if (
            str_contains($path, '.env') ||
            str_contains($path, '.git') ||
            str_starts_with($path, 'wp-admin') ||
            str_starts_with($path, 'phpmyadmin') ||
            str_contains($path, 'etc/passwd')
        ) {
            Log::warning("[Firewall] Shubhali skaner so'rovi to'xtatildi: {$path}", [
                'ip'         => $ip,
                'user_agent' => $request->userAgent(),
                'method'     => $request->method(),
            ]);

            // IP bo'yicha qoidabuzarlik hisoblagichi
            $strikeKey = 'firewall_strikes_' . md5($ip);
            $strikes = (int) Cache::get($strikeKey, 0) + 1;
            Cache::put($strikeKey, $strikes, 600); // 10 daqiqa

            // Agar 5 martadan ko'p skan qilsa — avtomatik qora ro'yxatga kiritamiz
            if ($strikes >= 5) {
                $this->autoBlockIp($ip);
            }

            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }

    /**
     * IP ni avtomatik qora ro'yxatga qo'shish
     */
    protected function autoBlockIp(string $ip): void
    {
        $file = storage_path('app/security_blocked_ips.json');
        $blocked = [];
        if (File::exists($file)) {
            $data = json_decode((string) File::get($file), true);
            $blocked = is_array($data) ? $data : [];
        }

        if (!in_array($ip, $blocked, true)) {
            $blocked[] = $ip;
            if (!File::isDirectory(dirname($file))) {
                File::makeDirectory(dirname($file), 0775, true, true);
            }
            File::put($file, json_encode(array_values(array_unique($blocked)), JSON_PRETTY_PRINT));
            Cache::forget('firewall_blocked_ips');
            Log::alert("[Firewall] IP avtomatik bloklandi (5+ xavfli skan): {$ip}");
        }
    }
}
