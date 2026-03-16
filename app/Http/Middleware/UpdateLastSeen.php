<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Har bir autentifikatsiyalangan API so'rovida last_seen_at ni yangilaydi.
     * Ortiqcha DB yozishlarni kamaytirish uchun 2 daqiqalik kesh ishlatiladi.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $cacheKey = 'user_seen_' . $user->id;

            if (! Cache::has($cacheKey)) {
                $user->timestamps = false;
                $user->update(['last_seen_at' => now()]);
                Cache::put($cacheKey, true, now()->addMinutes(2));
            }
        }

        return $next($request);
    }
}
