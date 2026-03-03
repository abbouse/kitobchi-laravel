<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig; // Agar iOS kerak bo'lsa
use Illuminate\Support\Facades\Log;

class PushController extends Controller
{
    public function sendPush(Request $request)
    {
        $request->validate([
            'app_key' => 'required|in:kitobchi,business,courier',
            'title'   => 'required|string|max:255',
            'body'    => 'required|string|max:1000',
            'tokens'  => 'required',
            'data'    => 'sometimes|array',
        ]);

        // Tokenlarni tozalash
        $rawTokens = $request->input('tokens');

// Agar string bo'lsa – JSON dekod qilish
if (is_string($rawTokens)) {
    $decoded = json_decode($rawTokens, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $tokens = array_filter($decoded, 'is_string');
    } else {
        $tokens = [$rawTokens]; // bitta token sifatida qabul qil
    }
} elseif (is_array($rawTokens)) {
    $tokens = array_filter($rawTokens, 'is_string');
} else {
    return $this->errorResponse('Invalid tokens format', 422);
}

        if (empty($tokens)) {
            return $this->errorResponse('At least one token is required', 422);
        }

        $project = $request->app_key;

        try {
            // Firebase Messaging obyekti
            $messaging = Firebase::project($project)->messaging();

            // Notification yaratish
            $notification = Notification::fromArray([
                'title' => $request->title,
                'body'  => $request->body,
            ]);

            // AndroidConfig (channel_id va priority uchun)
            $androidConfig = AndroidConfig::fromArray([
                'priority' => 'high', // 'normal' yoki 'high'
                'notification' => [
                    'channel_id' => 'channel', // Android 8.0+ uchun
                    'sound' => 'default',
                ],
            ]);

            // iOS uchun ApnsConfig (ixtiyoriy – agar kerak bo'lsa)
            $apnsConfig = ApnsConfig::fromArray([
                'priority' => 'high',
                'headers' => [
                    'apns-priority' => '10', // Immediate
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $request->title,
                            'body'  => $request->body,
                        ],
                        'badge' => 1,
                        'sound' => 'default',
                    ],
                ],
            ]);

            // CloudMessage yaratish
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withAndroidConfig($androidConfig)
                ->withApnsConfig($apnsConfig); // iOS uchun

            // Data qo'shish (agar mavjud bo'lsa)
            if ($request->has('data')) {
                $data = $request->input('data');
                // Barcha qiymatlarni string ga aylantirish (FCM talabi)
                $data = array_map(function ($value) {
                    return is_scalar($value) ? (string) $value : json_encode($value);
                }, $data);
                $message = $message->withData($data);
            }

            // Multicast yuborish (bir xabar ko'p tokenlarga)
            $report = $messaging->sendMulticast($message, $tokens);

            // Natijalarni tahlil qilish
            $sent = $report->successes()->count();
            $failed = $report->failures()->count();
            $invalidTokens = $report->invalidTokens(); // Malformed tokenlar

            // *** SIZNING QO'SHGAN QISMINIZ – BATAFSIL LOGGING ***
            foreach ($report->failures() as $failure) {
                $error = $failure->error();
                Log::error('FCM Message Failure', [
                    'project'     => $project,
                    'token'       => $failure->target()->value(), // Qaysi token uchun xato
                    'error_code'  => $error->errorCode(), // Xato kodi (masalan, "UNREGISTERED")
                    'error_message' => $error->errorMessage(), // Batafsil xato xabari
                    'reason'      => $error->reason(), // Xatoning sababi (masalan, "Invalid registration token")
                    'full_error'  => print_r($error, true), // To'liq ob'ekt (debug uchun)
                ]);
            }
            // *******************************

            // Muvaffaqiyatsiz tokenlarni bazadan o'chirish (tavsiya)
            if (!empty($invalidTokens)) {
                Log::warning('Invalid tokens detected', ['tokens' => $invalidTokens]);
                // Bu yerda DB dan o'chirish logikasi qo'shing, masalan:
                // foreach ($invalidTokens as $token) { DB::table('devices')->where('token', $token)->delete(); }
            }

            return response()->json([
                'success'         => $sent > 0,
                'project'         => $project,
                'sent'            => $sent,
                'failed'          => $failed,
                'invalid_tokens'  => $invalidTokens,
                'message'         => $sent > 0
                    ? "$sent ta qurilmaga muvaffaqiyatli yuborildi"
                    : "Hech qanday xabar yuborilmadi (failed: $failed)",
            ]);

        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            // FCM maxsus xatolari
            Log::error('FCM Messaging Exception', [
                'project' => $project,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(), // Batafsil FCM xatolari
            ]);
            return $this->errorResponse('FCM xatosi: ' . $e->getMessage(), 422);

        } catch (\Exception $e) {
            // Umumiy xatolar (masalan, autentifikatsiya)
            Log::error('FCM Umumiy Xatosi', [
                'project' => $project,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Xabar yuborishda xato: ' . $e->getMessage(), 500);
        }
    }

    private function errorResponse(string $message, int $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }
}