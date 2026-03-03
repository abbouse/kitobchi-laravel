<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerContest;
use App\Models\SellerContestParticipant;
use App\Models\User;
use App\Models\Seller;
use App\Models\SellerStaffLog; // ✅ LOG MODEL
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ContestsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    /**
     * ✅ OWNER DO'KON ID QAYTARADI
     */
    private function getStoreSellerId($seller)
    {
        return $seller->parent_id ?: $seller->id;
    }

    /**
     * ✅ CONTEST ACCESS: FAQAT OWNER YOKI ADMIN
     */
    private function hasContestAccess($seller)
    {
        // parent_id = NULL → OWNER → FULL ACCESS
        // parent_id mavjud + role=1 → ADMIN → FULL ACCESS
        return !$seller->parent_id || $seller->role == 1;
    }

    /**
     * ✅ LOG YOZISH (FAQAT AMAL UCHUN)
     */
    private function writeLog($staff, $action, $details = '')
    {
        $storeSellerId = $this->getStoreSellerId($staff);
        
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'seller_id' => $storeSellerId,
            'text' => "Hodim: {$staff->firstname} {$staff->lastname} ({$staff->role}) → {$action}" . ($details ? " | {$details}" : ''),
        ]);
    }

    /**
     * ✅ VIEW ONLY - LOG YO'Q
     */
    public function getContests()
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK (LOG YO'Q - FAQAT KO'RISH)
        if (!$this->hasContestAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Contests available only for Admin and Owner.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $contests = SellerContest::where('seller_id', $storeSellerId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($contest) {
                return [
                    'id' => $contest->id,
                    'title' => $contest->title,
                    'created_at' => optional($contest->created_at)->format('d.m.Y, H:i') ?? Carbon::now()->format('d.m.Y, H:i'),
                    'end_date' => optional($contest->end_date)->format('d.m.Y, H:i') ?? null,
                    'status' => $contest->status,
                    'participants_count' => $contest->participants()->count() ?? 0,
                    'winner_count' => $contest->winner_count,
                    'winners' => $contest->winners,
                ];
            });

        return response()->json(['success' => true, 'data' => $contests], 200);
    }

    /**
     * ✅ VIEW ONLY - LOG YO'Q
     */
    public function getContestDetails(Request $request, $contestId)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK (LOG YO'Q - FAQAT KO'RISH)
        if (!$this->hasContestAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Contests available only for Admin and Owner.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $contest = SellerContest::where('id', $contestId)
            ->where('seller_id', $storeSellerId)
            ->first();

        if (!$contest) {
            return response()->json(['success' => false, 'message' => 'Tanlov topilmadi'], 404);
        }

        if ($contest->status !== 'ended' && optional($contest->end_date)->isPast()) {
            $this->determineWinners($contest);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $contest->id,
                'title' => $contest->title,
                'description' => $contest->description,
                'created_at' => optional($contest->created_at)->format('d.m.Y, H:i') ?? Carbon::now()->format('d.m.Y, H:i'),
                'end_date' => optional($contest->end_date)->format('d.m.Y, H:i') ?? null,
                'status' => $contest->status,
                'participants_count' => $contest->participants()->count(),
                'winner_count' => $contest->winner_count,
                'winners' => $contest->winners,
            ]
        ], 200);
    }

    /**
     * ✅ DOWNLOAD - LOG YOZILADI
     */
    public function downloadParticipants(Request $request, $contestId)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK
        if (!$this->hasContestAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Contests available only for Admin and Owner.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $contest = SellerContest::where('id', $contestId)
            ->where('seller_id', $storeSellerId)
            ->first();

        if (!$contest) {
            return response()->json(['success' => false, 'message' => 'Tanlov topilmadi'], 404);
        }

        $participants = $contest->participants()->with('user')->get();
        $csvContent = "ID,Ism,Familiya\n";
        foreach ($participants as $participant) {
            $user = $participant->user;
            $csvContent .= "{$participant->participant_id},{$user->first_name},{$user->last_name}\n";
        }

        $fileName = "contest_{$contestId}_participants.csv";
        Storage::disk('public')->put($fileName, $csvContent);

        // ✅ LOG YOZISH (FAQAT DOWNLOAD UCHUN)
        $this->writeLog($seller, 'Tanlov ishtirokchilarini yuklab oldi', "{$contest->title} | {$participants->count()} kishi");

        return response()->json([
            'success' => true,
            'message' => 'Ishtirokchilar ro\'yxati yuklab olindi',
            'file_url' => Storage::url($fileName),
        ], 200);
    }

    /**
     * ✅ CREATE CONTEST - LOG YOZILADI
     */
    public function createContest(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK
        if (!$this->hasContestAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Contests available only for Admin and Owner.'
            ], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'end_date' => 'required|date|after:now',
            'winner_count' => 'required|integer|min:1',
        ]);

        $storeSellerId = $this->getStoreSellerId($seller);

        $contest = SellerContest::create([
            'seller_id' => $storeSellerId,
            'title' => $request->title,
            'description' => $request->description,
            'end_date' => $request->end_date,
            'status' => 'pending',
            'winner_count' => $request->winner_count,
        ]);

        // ✅ LOG YOZISH (FAQAT CREATE UCHUN)
        $this->writeLog($seller, 'Yangi tanlov yaratdi', "{$contest->title} | G'oliblar soni: {$contest->winner_count}");

        return response()->json([
            'success' => true,
            'message' => 'Tanlov muvaffaqiyatli yaratildi',
            'data' => [
                'id' => $contest->id,
                'title' => $contest->title,
                'created_at' => optional($contest->created_at)->format('d.m.Y, H:i') ?? Carbon::now()->format('d.m.Y, H:i'),
                'end_date' => optional($contest->end_date)->format('d.m.Y, H:i') ?? null,
                'status' => $contest->status,
                'winner_count' => $contest->winner_count,
            ]
        ], 201);
    }

    /**
     * ✅ END CONTEST - LOG YOZILADI
     */
    public function endContest($contestId)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK
        if (!$this->hasContestAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Contests available only for Admin and Owner.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $contest = SellerContest::where('id', $contestId)
            ->where('seller_id', $storeSellerId)
            ->first();

        if (!$contest) {
            return response()->json(['success' => false, 'message' => 'Tanlov topilmadi'], 404);
        }

        if ($contest->status === 'ended') {
            return response()->json(['success' => false, 'message' => 'Tanlov allaqachon tugatilgan'], 400);
        }

        $oldStatus = $contest->status;
        $this->determineWinners($contest);
        $participantsCount = $contest->participants()->count();
        $winnerCount = count($contest->winners ?? []);

        // ✅ LOG YOZISH (FAQAT END UCHUN)
        $this->writeLog($seller, 'Tanlovni tugatdi va g\'oliblar aniqladi', 
            "{$contest->title} | Holat: {$oldStatus} → ended | Ishtirokchilar: {$participantsCount} | G'oliblar: {$winnerCount}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Tanlov muvaffaqiyatli tugatildi va g\'oliblar aniqlandi',
        ], 200);
    }

    /**
     * ✅ DELETE CONTEST - LOG YOZILADI
     */
    public function deleteContest($contestId)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK
        if (!$this->hasContestAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Contests available only for Admin and Owner.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $contest = SellerContest::where('id', $contestId)
            ->where('seller_id', $storeSellerId)
            ->first();

        if (!$contest) {
            return response()->json(['success' => false, 'message' => 'Tanlov topilmadi'], 404);
        }

        if ($contest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Faqat yangi yaratilgan tanlovlarni o\'chirish mumkin'], 400);
        }

        $contestTitle = $contest->title;
        $contest->delete();

        // ✅ LOG YOZISH (FAQAT DELETE UCHUN)
        $this->writeLog($seller, 'Tanlovni o\'chirib yubordi', $contestTitle);

        return response()->json([
            'success' => true,
            'message' => 'Tanlov muvaffaqiyatli o\'chirildi',
        ], 200);
    }

    protected function determineWinners(SellerContest $contest)
    {
        if ($contest->status === 'ended') {
            return;
        }

        $participants = $contest->participants()->with('user')->get();
        if ($participants->isEmpty()) {
            $contest->status = 'ended';
            $contest->winners = [];
            $contest->save();
            return;
        }

        $winnerCount = min($contest->winner_count, $participants->count());
        $winnerParticipants = $participants->shuffle()->take($winnerCount);

        $winners = $winnerParticipants->map(function ($participant) {
            $user = $participant->user;
            return [
                'id' => $user->id,
                'name' => $user->name,
                'lastname' => $user->lastname,
                'phone_number' => $user->phone_number,
            ];
        })->toArray();

        $contest->winners = $winners;
        $contest->status = 'ended';
        $contest->save();
    }
}