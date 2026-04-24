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

    public function getContest(Request $request, $sellerId)
    {
        $user = Auth::guard('user')->user();
        $contest = SellerContest::with('participants')
            ->where('seller_id', $sellerId)
            ->where('status', 'approved')
            ->first();

        if (!$contest) {
            return $this->error('Tanlov topilmadi', 404);
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

        return $this->success(['data' => [$data]]);
    }

    public function joinToContest(Request $request, SellerContest $contest)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return $this->error('Autentifikatsiya xatosi', 401);
        }
        if (!$contest) {
            return $this->error('Tanlov topilmadi', 404);
        }

        if ($contest->status == 'ended' || $contest->status == 'pending' || optional($contest->end_date)->isPast()) {
            return $this->error('Tanlov tugagan', 400);
        }

        $contestParticipant = SellerContestParticipant::where('seller_contest_id', $contest->id)
            ->where('participant_id', $user->id)
            ->first();

        if ($contestParticipant) {
            return $this->error('Siz allaqachon ishtirok etgansiz', 400);
        } else {
            $contestParticipant = new SellerContestParticipant();
            $contestParticipant->seller_contest_id = $contest->id;
            $contestParticipant->participant_id = $user->id;
            $contestParticipant->save();
        }
        return $this->success(['message' => 'Muvaffaqiyatli ishtirok etdingiz']);
    }
    
    public function getUserContests(Request $request)
{
    $user = Auth::guard('user')->user();
    if (!$user) {
        return $this->error('Autentifikatsiya xatosi', 401);
    }
    $participatedContests = SellerContestParticipant::where('participant_id', $user->id)
        ->with(['contest' => function($q) {
            $q->with(['participants.user:id,name,lastname,avatar', 'seller:id,shop_name',]);
        }])
        ->latest()
        ->get();
    if ($participatedContests->isEmpty()) {
        return $this->success([
            'data' => [],
            'message' => 'Siz hali hech qanday tanlovda ishtirok etmagansiz'
        ]);
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
    return $this->success(['data' => $data]);
}
}
