<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reel;
use Illuminate\Http\Request;

class ReelController extends Controller
{
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
                return response()->json([
                    'ok' => true,
                    'data' => [],
                    'message' => 'Hozircha videolar mavjud emas'
                ], 200);
            }

            return response()->json([
                'ok' => true,
                'data' => $reels,
                'message' => 'Muvaffaqiyatli yuklandi'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Serverda xatolik: ' . $e->getMessage()
            ], 500);
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
            return response()->json(['ok' => false, 'message' => 'Topilmadi'], 404);
        }

        return response()->json(['ok' => true, 'data' => $reel], 200);
    }
}