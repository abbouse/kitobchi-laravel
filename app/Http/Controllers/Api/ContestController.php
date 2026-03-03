<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SellerContest;
use App\Models\SellerContestParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ContestController extends Controller
{
    public function getContest(Request $request, $sellerId)
    {
        $user = Auth::guard('user')->user();
        $contest = SellerContest::with('participants')
            ->where('seller_id', $sellerId)
            ->where('status', 'approved')
            ->first();

        if (!$contest) {
            return response()->json(['status' => 'error', 'message' => 'Tanlov topilmadi'], 201);
        }

        $contestParticipant = null;
        if ($user) {
            $contestParticipant = SellerContestParticipant::where('seller_contest_id', $contest->id)
                ->where('participant_id', $user->id)
                ->first();
        }

        $data = [
            'id' => $contest->id,
            'title' => $contest->title,
            'description' => $contest->description,
            'created_at' => optional($contest->created_at)->format('d.m.Y, H:i') ?? Carbon::now()->format('d.m.Y, H:i'),
            'end_date' => optional($contest->end_date)->format('d F') ?? null,
            'status' => $contest->status,
            'participants_count' => $contest->participants->count(),
            'winner_count' => $contest->winner_count,
            'winners' => $contest->winners,
            'is_date_expired' => optional($contest->end_date)->isPast() ?? false,
            'is_me_joined' => $contestParticipant ? true : false
        ];

        return response()->json(['status' => 'success', 'data' => [$data]], 201);
    }

    public function joinToContest(Request $request, SellerContest $contest)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
        return response()->json(['status' => 'error', 'message' => 'Autentifikatsiya xatosi'], 401);
    }
        if (!$contest) {
            return response()->json(['status' => 'error', 'message' => 'Tanlov topilmadi'], 201);
        }

        if ($contest->status == 'ended' || $contest->status == 'pending' || optional($contest->end_date)->isPast()) {
            return response()->json(['status' => 'error', 'message' => 'Tanlov tugagan'], 201);
        }

        $contestParticipant = SellerContestParticipant::where('seller_contest_id', $contest->id)
            ->where('participant_id', $user->id)
            ->first();

        if ($contestParticipant) {
            return response()->json(['status' => 'error', 'message' => 'Siz allaqachon ishtirok etgansiz'], 201);
        }else{
$contestParticipant = new SellerContestParticipant();
$contestParticipant->seller_contest_id = $contest->id;
$contestParticipant->participant_id = $user->id;
$contestParticipant->save();
        }
        return response()->json(['status' => 'success', 'message' => 'Muvaffaqiyatli ishtirok etdingiz'], 201);
    }
    
    public function getUserContests(Request $request)
{
    $user = Auth::guard('user')->user();
    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Autentifikatsiya xatosi'
        ], 401);
    }
    $participatedContests = SellerContestParticipant::where('participant_id', $user->id)
        ->with(['contest' => function($q) {
            $q->with(['participants.user:id,name,lastname,avatar', 'seller:id,shop_name',]);
        }])
        ->latest()
        ->get();
    if ($participatedContests->isEmpty()) {
        return response()->json([
            'status' => 'success',
            'data' => [],
            'message' => 'Siz hali hech qanday tanlovda ishtirok etmagansiz'
        ], 201);
    }
    $data = $participatedContests->map(function($participant) {
        $contest = $participant->contest;
        if (!$contest) return null;
        $lastTenParticipants = $contest->participants()
            ->with('user:id,name,lastname,avatar')
            ->latest()
            ->take(10)
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->user->id ?? null,
                    'name' => $p->user->name ?? '',
                    'lastname' => $p->user->lastname ?? '',
                    'profile_image' => $p->user->avatar ?? null,
                ];
            });
        return [
            'id' => $contest->id,
            'title' => $contest->title,
            'description' => $contest->description,
            'created_at' => optional($contest->created_at)->format('d.m.Y, H:i'),
            'end_date' => optional($contest->end_date)->format('d F'),
            'status' => $contest->status,
            'participants_count' => $contest->participants->count(),
            'winner_count' => $contest->winner_count,
            'is_date_expired' => $contest->end_date instanceof \Carbon\Carbon
                ? $contest->end_date->isPast()
                : false,
            'winners' => ($contest->status == 'ended' || optional($contest->end_date)->isPast())
                ? ($contest->winners ?? [])
                : [],
            'last_participants' => $lastTenParticipants,
            'seller_shop_name' => $contest->seller->shop_name ?? null,
        ];
    })->filter()->values();
    return response()->json([
        'status' => 'success',
        'data' => $data
    ], 201);
}
}
