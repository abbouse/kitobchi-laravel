<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\UserPositionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Kitobxon darajasi va klub nishonlari (ilova uchun). */
class UserProgressController extends Controller
{
    public function __construct(
        private readonly UserPositionService $positions,
        private readonly BadgeService $badges,
    ) {}

    /** GET book_club/badges?user_id= — barcha nishonlar (progress faqat egasiga). */
    public function badges(Request $request)
    {
        $me = Auth::guard('user')->user();
        $user = User::find((int) ($request->query('user_id') ?: $me?->id));
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $isOwner = $me && (int) $me->id === (int) $user->id;

        return response()->json([
            'status' => 'success',
            'is_owner' => $isOwner,
            'position' => $user->position,
            'data' => $this->badges->list($user, $isOwner),
            // Daraja tafsilotlari (xarid summasi) faqat egasiga
            'level' => $isOwner ? $this->positions->summary($user) : null,
        ]);
    }

    /** GET user/level — o'z darajasi, keyingisigacha qolgani va imtiyozlar. */
    public function level()
    {
        $user = Auth::guard('user')->user();

        return response()->json(['status' => 'success', 'data' => $this->positions->summary($user)]);
    }
}
