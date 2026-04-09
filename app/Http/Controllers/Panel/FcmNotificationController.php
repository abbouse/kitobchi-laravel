<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\FcmNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FcmNotificationController extends Controller
{
    /**
     * Target types va ularning tavsifi
     */
    private const TARGETS = [
        'users'    => ['label' => 'Foydalanuvchilar (xaridorlar)', 'icon' => 'bi-people',      'color' => 'accent'],
        'business' => ['label' => 'Sotuvchilar (business)',        'icon' => 'bi-shop-window',  'color' => 'warning'],
        'courier'  => ['label' => 'Kuryerlar',                     'icon' => 'bi-bicycle',       'color' => 'success'],
    ];

    public function index(Request $request)
    {
        $q = FcmNotifications::query();

        $tab = $request->get('tab', 'all');
        if ($tab !== 'all') {
            $q->where('who', $tab);
        }

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

        return view('panel.fcm-notifications.index',
            compact('notifications', 'counts', 'tab', 'targets'));
    }

    public function create()
    {
        $targets = self::TARGETS;

        // Token soni preview
        $tokenCounts = [];
        foreach (array_keys(self::TARGETS) as $type) {
            $userType = $type === 'users' ? 'user' : $type;
            $tokenCounts[$type] = DB::table('connected_devices')
                ->where('user_type', $userType)
                ->whereNotNull('fcm_token')
                ->distinct('fcm_token')
                ->count('fcm_token');
        }

        return view('panel.fcm-notifications.create', compact('targets', 'tokenCounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'who'         => 'required|in:users,business,courier',
        ]);

        // ── Notification saqlash ───────────────────────────────────
        $notif = FcmNotifications::create([
            'name'        => $request->name,
            'description' => $request->description,
            'who'         => $request->who,
        ]);

        // ── FCM tokenlarini olish ──────────────────────────────────
        $userType = $request->who === 'users' ? 'user' : $request->who;

        $tokens = DB::table('connected_devices')
            ->where('user_type', $userType)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->unique()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            return redirect()->route('panel.fcm-notifications.index')
                ->with('warning', "Bildirishnoma saqlandi, lekin yuborish uchun faol token topilmadi.");
        }

        // ── Push yuborish ──────────────────────────────────────────
        $appKey = match($request->who) {
            'business' => 'business',
            'courier'  => 'courier',
            default    => 'kitobchi',
        };

        $pushData = [
            'app_key' => $appKey,
            'title'   => $notif->name,
            'body'    => $notif->description,
            'tokens'  => $tokens,
            'data'    => [
                'type' => 'general',
                'id'   => (string) $notif->id,
            ],
        ];

        try {
            $pushRequest = new \Illuminate\Http\Request();
            $pushRequest->replace($pushData);
            app(\App\Http\Controllers\PushController::class)->sendPush($pushRequest);

            $label = self::TARGETS[$request->who]['label'];
            return redirect()->route('panel.fcm-notifications.index')
                ->with('success', "Bildirishnoma {$label}ga yuborildi. ({$notif->id})");
        } catch (\Exception $e) {
            Log::error('FCM Panel Push Error: ' . $e->getMessage());
            return redirect()->route('panel.fcm-notifications.index')
                ->with('warning', "Bildirishnoma saqlandi, lekin yuborishda xato: " . $e->getMessage());
        }
    }

    public function destroy(FcmNotifications $fcmNotification)
    {
        $fcmNotification->delete();
        return back()->with('success', "Bildirishnoma o'chirildi.");
    }
}