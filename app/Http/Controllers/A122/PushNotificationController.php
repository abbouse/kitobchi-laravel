<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\FcmNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    private const TARGETS = [
        'users'    => ['label' => 'Foydalanuvchilar (xaridorlar)', 'icon' => 'bi-people', 'color' => 'accent'],
        'business' => ['label' => 'Sotuvchilar (business)', 'icon' => 'bi-shop-window', 'color' => 'warning'],
        'courier'  => ['label' => 'Kuryerlar', 'icon' => 'bi-bicycle', 'color' => 'success'],
    ];

    public function index(Request $request)
    {
        $q = FcmNotifications::query();

        $tab = $request->get('tab', 'all');
        if ($tab !== 'all') $q->where('who', $tab);

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('name', 'like', "%$s%")
                ->orWhere('description', 'like', "%$s%")
            );
        }

        $notifications = $q->latest()->paginate(20)->withQueryString();

        $counts = [
            'all'      => FcmNotifications::count(),
            'users'    => FcmNotifications::where('who', 'users')->count(),
            'business' => FcmNotifications::where('who', 'business')->count(),
            'courier'  => FcmNotifications::where('who', 'courier')->count(),
        ];

        $targets = self::TARGETS;

        return view('a122.push.index', compact('notifications', 'counts', 'tab', 'targets'));
    }

    public function create()
    {
        $targets = self::TARGETS;
        $tokenCounts = [];

        foreach (array_keys(self::TARGETS) as $type) {
            $userType = $type === 'users' ? 'user' : $type;
            $tokenCounts[$type] = DB::table('connected_devices')
                ->where('user_type', $userType)
                ->whereNotNull('fcm_token')
                ->distinct('fcm_token')
                ->count('fcm_token');
        }

        return view('a122.push.create', compact('targets', 'tokenCounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'who'         => 'required|in:users,business,courier',
        ]);

        $notif = FcmNotifications::create($data);

        $userType = $request->who === 'users' ? 'user' : $request->who;

        $tokens = DB::table('connected_devices')
            ->where('user_type', $userType)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->unique()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            return redirect()->route('admin.push.index')
                ->with('warning', 'Bildirishnoma saqlandi, lekin yuborish uchun faol token topilmadi.');
        }

        $appKey = match ($request->who) {
            'business' => 'business',
            'courier' => 'courier',
            default => 'kitobchi',
        };

        try {
            $pushRequest = new \Illuminate\Http\Request();
            $pushRequest->replace([
                'app_key' => $appKey,
                'title' => $notif->name,
                'body' => $notif->description,
                'tokens' => $tokens,
                'data' => ['type' => 'general', 'id' => (string) $notif->id],
                // MUHIM (tuzatildi): bu sun'iy Request in-process yasalgani
                // uchun na X-Push-Secret header, na panel auth bor — secretni
                // shu yerda qo'shmasak, PushController::sendPush() doim 401
                // "Unauthorized push request" qaytarardi (aynan shu bug
                // "boshqaruvda push ishlamayapti" muammosining sababi edi).
                'secret' => \App\Http\Controllers\PushController::sharedSecret(),
            ]);

            app(\App\Http\Controllers\PushController::class)->sendPush($pushRequest);

            return redirect()->route('admin.push.index')->with('success', 'Bildirishnoma yuborildi.');
        } catch (\Throwable $e) {
            Log::error('A122 FCM push error: ' . $e->getMessage());

            return redirect()->route('admin.push.index')
                ->with('warning', 'Bildirishnoma saqlandi, lekin yuborishda xato yuz berdi.');
        }
    }

    public function destroy(FcmNotifications $notification)
    {
        $notification->delete();
        return redirect()->route('admin.push.index')->with('success', "O'chirildi.");
    }
}
