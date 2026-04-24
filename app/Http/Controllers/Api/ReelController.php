<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reel;
use Illuminate\Http\Request;

class ReelController extends Controller
{
    private function success(array $payload = [], int $status = 200)
    {
        return response()->json(array_merge([
            'status' => 'success',
            'ok' => true,
        ], $payload), $status);
    }

    private function error(string $message, int $status = 400)
    {
        return response()->json([
            'status' => 'error',
            'ok' => false,
            'message' => $message,
            'error' => $message,
        ], $status);
    }

    /**
     * Barcha reelslarni ichki videolari bilan birga qaytaradi
     */
    public function index()
    {
        try {
            // Eager Loading: items'larni ham tartib bilan yuklaymiz
            $reels = Reel::with(['items'])
                ->orderBy('order', 'asc')
                ->get();

            // Agar ma'lumot bo'sh bo'lsa
            if ($reels->isEmpty()) {
                return $this->success([
                    'data' => [],
                    'message' => 'Hozircha videolar mavjud emas'
                ], 200);
            }

            return $this->success([
                'data' => $reels,
                'message' => 'Muvaffaqiyatli yuklandi'
            ], 200);

        } catch (\Exception $e) {
            return $this->error('Serverda xatolik: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Alohida bitta reeelni ko'rish (ixtiyoriy)
     */
    public function show($id)
    {
        $reel = Reel::with(['items' => function ($query) {
            $query->orderBy('order', 'asc');
        }])->find($id);

        if (!$reel) {
            return $this->error('Topilmadi', 404);
        }

        return $this->success(['data' => $reel], 200);
    }
}
