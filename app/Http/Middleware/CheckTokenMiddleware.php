<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user(); 

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => "Noma'lum foydalanuvchi!"
            ], 401);
        }
        \Illuminate\Support\Facades\Auth::setUser($user);
        return $next($request);
    }
}
