<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function sendReport(Request $request)
{
    $user = Auth::guard('user')->user();

    if (!$user) {
        return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 401);
    }

    // 2. Validatsiya
    $request->validate([
        'reportable_id'   => 'required|integer',
        'reportable_type' => 'required|string',
        'reason'          => 'required|string',
        'comment'         => 'nullable|string',
    ]);

    $map = [
        'conversation_message' => \App\Models\Message::class,
        'book_club'            => \App\Models\BookClub::class, 
        // Agar model nomi Post bo'lsa, Post::class deb o'zgartiring
    ];

    if (!array_key_exists($request->reportable_type, $map)) {
        return response()->json(['status' => 'error', 'message' => 'Noma\'lum bo\'lim'], 400);
    }

    $modelClass = $map[$request->reportable_type];

    // 4. O'sha narsa haqiqatda bormi?
    $itemExists = $modelClass::where('id', $request->reportable_id)->exists();
    if (!$itemExists) {
        return response()->json(['status' => 'error', 'message' => 'Shikoyat obyekti topilmadi'], 404);
    }

    // 5. Saqlash
    try {
        $report = new Report();
        $report->user_id = $user->id; // $user yuqorida tekshirildi, shuning uchun ternary shart emas
        $report->reportable_type = $request->reportable_type;
        $report->reportable_id = $request->reportable_id;
        
        // MANA SHU YERNI TUZATDIK:
        $report->reason = $request->reason; 
        
        $report->comment = $request->comment;
        $report->save();

        return response()->json(['status' => 'success'], 201);

    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Xatolik: ' . $e->getMessage()], 500);
    }
}
}