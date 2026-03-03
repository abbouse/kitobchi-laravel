<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PaymeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization');
        // \Log::info('authorization: ' . $authorization);
        if (!$authorization || !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $authorization, $matches)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32504,
                    'message' => [
                        "uz" => "Avtorizatsiyadan otishda xatolik",
                        "ru" => "Ошибка аутентификации",
                        "en" => "Auth error"
                    ]
                ]
            ]);
        }
        // \Log::info("Matches:" . $matches[1]);
        $decodedCredentials = base64_decode($matches[1]);
        // \Log::info("Decoded credentials:" . $decodedCredentials);
        list($username, $password) = explode(':', $decodedCredentials);
        // \Log::info('Username and Password: ' . $username . ':' . $password);
        $expectedUsername = "Paycom";
        $expectedPassword = "YUIUn@qQnj6&0?9u38I91yCy4mb%#P7FbFs9";

        if ($username !== $expectedUsername || $password !== $expectedPassword) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32504,
                    'message' => [
                        "uz" => "Avtorizatsiyadan otishda xatolik",
                        "ru" => "Ошибка аутентификации",
                        "en" => "Auth error"
                    ]
                ]
            ]);
        }

        return $next($request);
    }
}