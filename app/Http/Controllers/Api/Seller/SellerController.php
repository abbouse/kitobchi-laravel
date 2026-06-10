<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\Seller;
use App\Models\SellerAd;
use App\Models\SellerBanLog;
use App\Models\SellerContest;
use App\Models\SellerNotification;
use App\Models\SellerStaffLog;
use App\Models\SellerTransaction;
use App\Models\Message;
use App\Models\SellerLocation;
use App\Services\PasswordResetService;
use App\Services\SellerReputationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SellerController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
        private readonly SellerReputationService $sellerReputationService,
    )
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
     * ✅ OWNER/ADMIN ACCESS
     */
    private function hasOwnerAdminAccess($seller)
    {
        return !$seller->parent_id || $seller->role == 1;
    }

    /**
     * ✅ SETTINGS ACCESS: FAQAT OWNER
     */
    private function hasOwnerAccess($seller)
    {
        return !$seller->parent_id;
    }
    private function writeLog($staff, $action, $details = '')
    {
        $storeSellerId = $this->getStoreSellerId($staff);
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'text' => "Hodim: {$staff->firstname} {$staff->lastname} ({$staff->role}) → {$action}" . ($details ? " | {$details}" : ''),
        ]);
    }

    public function getDevices(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ HAR KIM O'Z QURILMALARINI KO'radi
        $currentToken = $request->bearerToken();
        $currentTokenHash = hash('sha256', explode('|', $currentToken)[1]);

        $devices = DB::table('connected_devices')
            ->join('personal_access_tokens', 'connected_devices.token', '=', 'personal_access_tokens.token')
            ->where('personal_access_tokens.tokenable_id', $seller->id) // ✅ O'Z ID
            ->where('personal_access_tokens.tokenable_type', get_class($seller))
            ->select(
                'connected_devices.id',
                'connected_devices.device_name',
                'connected_devices.device_id',
                'connected_devices.platform',
                'connected_devices.created_at',
                'personal_access_tokens.last_used_at',
                'personal_access_tokens.token'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => $devices->map(function ($device) use ($currentTokenHash) {
                return [
                    'id' => $device->id,
                    'name' => $device->device_name,
                    'device_id' => $device->device_id,
                    'platform' => $device->platform,
                    'last_used_at' => $device->last_used_at,
                    'created_at' => $device->created_at,
                    'is_current' => $device->token === $currentTokenHash,
                ];
            }),
        ], 200);
    }

    public function removeDevice(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ HAR KIM O'Z QURILMASINI O'CHIRA OLADI
        $deviceId = $request->input('device_id');
        if (!$deviceId) {
            return response()->json(['success' => false, 'message' => 'Device ID kerak'], 400);
        }
        $this->writeLog($seller, 'Profilga kirilgan qurilmani o\'chirdi');
        $device = DB::table('connected_devices')
            ->where('device_id', $deviceId)
            ->whereIn('token', $seller->tokens()->pluck('token')) // ✅ O'Z TOKENLARI
            ->first();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Qurilma topilmadi'], 404);
        }

        DB::transaction(function () use ($device, $seller) {
            $seller->tokens()->where('token', $device->token)->delete();
            DB::table('connected_devices')->where('device_id', $device->device_id)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Qurilma va token muvaffaqiyatli o\'chirildi',
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Only Owner can update store status.'
            ], 403);
        }
        $main_location = SellerLocation::where('seller_id', $seller->id)
            ->where('is_main', true)
            ->get();
            if (!$main_location) {
                return response()->json([
                'success' => false, 
                'message' => 'Access denied'
            ], 403);
            }
        $seller->is_hidden = $seller->is_hidden ? false : true;
        $seller->save();
        return response()->json([
            'success' => true,
            'is_hidden' => $seller->is_hidden,
            'message' => 'Do\'kon statusi yangilandi',
        ], 200);
    }

    public function notifications(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$this->hasOwnerAdminAccess($seller)) {
            return response()->json([
                'success' => true,
                'data' => [] // Bo'sh array
            ], 200);
        }

        $storeSellerId = $this->getStoreSellerId($seller); // ✅ OWNER ID
        $notifications = SellerNotification::where('seller_id', $storeSellerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ], 200);
    }

    public function markAsRead(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $notificationId = $request->input('notification_id');

        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ FAQAT OWNER/ADMIN
        if (!$this->hasOwnerAdminAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Only Owner and Admin can manage notifications.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller); // ✅ OWNER ID
        $notification = SellerNotification::where('seller_id', $storeSellerId)
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json(['success' => false, 'message' => 'Xabar topilmadi'], 404);
        }

        $notification->isRead = true;
        $notification->save();

        return response()->json(['success' => true], 200);
    }

    public function markAllAsRead(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ FAQAT OWNER/ADMIN
        if (!$this->hasOwnerAdminAccess($seller)) {
            return response()->json([
                'success' => true,
                'message' => 'O\'qilmagan xabarnomalar mavjud emas'
            ], 200);
        }

        $storeSellerId = $this->getStoreSellerId($seller); // ✅ OWNER ID
        $notifications = SellerNotification::where('seller_id', $storeSellerId)
            ->where('isRead', false)
            ->get();

        if ($notifications->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'O\'qilmagan xabarnomalar mavjud emas'
            ], 200);
        }

        foreach ($notifications as $notification) {
            $notification->isRead = true;
            $notification->save();
        }

        return response()->json(['success' => true], 200);
    }

    public function notificationsCount(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ FAQAT OWNER/ADMIN
        if (!$this->hasOwnerAdminAccess($seller)) {
            return response()->json([
                'success' => true,
                'notifications' => 0 // Bo'sh count
            ], 200);
        }

        $storeSellerId = $this->getStoreSellerId($seller); // ✅ OWNER ID
        $notifications = Cache::remember(
            "seller:{$storeSellerId}:notifications_unread_count:v1",
            now()->addSeconds(30),
            fn () => (int) SellerNotification::where('seller_id', $storeSellerId)
                ->where('isRead', false)
                ->count()
        );

        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ], 200);
    }

    public function menuBadgeSummary(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $data = Cache::remember(
            "seller:{$storeSellerId}:menu_badge_summary:v1",
            now()->addSeconds(45),
            function () use ($storeSellerId) {
                $messagesUnread = Message::query()
                    ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
                    ->where('conversations.type', 'shop')
                    ->where('conversations.shop_id', $storeSellerId)
                    ->where('messages.sender_type', '!=', Seller::class)
                    ->where('messages.is_read', false)
                    ->where('messages.is_deleted', false)
                    ->count();

                $warningsUnread = SellerBanLog::getUnreadCount($storeSellerId);
                $transactionsPending = SellerTransaction::query()
                    ->where('seller_id', $storeSellerId)
                    ->where('status', 'pending')
                    ->count();
                $eventsPending = SellerContest::query()
                    ->where('seller_id', $storeSellerId)
                    ->where('status', 'pending')
                    ->count();
                $adsPending = SellerAd::query()
                    ->where('seller_id', $storeSellerId)
                    ->where(function ($q) {
                        $q->where('moderation', 'pending')
                            ->orWhere('paymentStatus', 'pending');
                    })
                    ->count();
                $notificationsUnread = SellerNotification::query()
                    ->where('seller_id', $storeSellerId)
                    ->where('isRead', false)
                    ->count();

                return [
                    'messages' => (int) $messagesUnread,
                    'warnings' => (int) $warningsUnread,
                    'transactions' => (int) $transactionsPending,
                    'events' => (int) $eventsPending,
                    'ads' => (int) $adsPending,
                    'notifications' => (int) $notificationsUnread,
                ];
            }
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function menuReputationSummary(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $storeSeller = Seller::query()->find($storeSellerId);

        if (!$storeSeller) {
            return response()->json(['success' => false, 'message' => 'Seller not found'], 404);
        }

        $data = Cache::remember(
            "seller:{$storeSellerId}:menu_reputation_summary:v2",
            now()->addMinutes(3),
            function () use ($storeSellerId, $storeSeller) {
                $reputation = $this->sellerReputationService->recalculateSeller($storeSeller, false);
                $metrics = $reputation['metrics'] ?? [];

                $booksQuery = Books::query()->where('seller_id', $storeSellerId)->where('is_hidden', 0);
                $stationeryQuery = Stationery::query()->where('seller_id', $storeSellerId)->where('is_hidden', 0);

                $bookAvg = (float) ($booksQuery->where('ugc_aggregate_score', '>', 0)->avg('ugc_aggregate_score') ?? 0);
                $stationeryAvg = (float) ($stationeryQuery->where('ugc_aggregate_score', '>', 0)->avg('ugc_aggregate_score') ?? 0);
                $bookCount = (int) Books::query()->where('seller_id', $storeSellerId)->where('is_hidden', 0)->count();
                $stationeryCount = (int) Stationery::query()->where('seller_id', $storeSellerId)->where('is_hidden', 0)->count();
                $activeBooks = (int) Books::query()
                    ->where('seller_id', $storeSellerId)
                    ->where('is_hidden', 0)
                    ->where('status', 1)
                    ->where('count', '>', 0)
                    ->count();
                $activeStationery = (int) Stationery::query()
                    ->where('seller_id', $storeSellerId)
                    ->where('is_hidden', 0)
                    ->where('status', 1)
                    ->where('stock', '>', 0)
                    ->count();

                $ratedSources = collect([$bookAvg, $stationeryAvg])->filter(fn ($value) => $value > 0);
                $productRating = $ratedSources->isNotEmpty()
                    ? round((float) $ratedSources->avg(), 2)
                    : round((float) ($storeSeller->rating ?? SellerReputationService::BASELINE_PUBLIC_RATING), 2);

                $soldProducts = (int) DB::table('seller_order_items')
                    ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
                    ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                    ->where('seller_order_items.seller_id', $storeSellerId)
                    ->whereNull('seller_order_items.cancelled_at')
                    ->whereNotNull('solds.completed_at')
                    ->where(function ($query) {
                        $query->where('solds.payment_status_code', 'paid')
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('solds.payment_status_code')
                                    ->where('solds.paymentStatus', 2);
                            });
                    })
                    ->sum('seller_order_items.quantity');

                $returnedProducts = (int) DB::table('seller_order_items')
                    ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
                    ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
                    ->where('seller_order_items.seller_id', $storeSellerId)
                    ->where(function ($query) {
                        $query->where('solds.status_code', 'returned')
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('solds.status_code')
                                    ->where('solds.status', 'R');
                            });
                    })
                    ->sum('seller_order_items.quantity');

                $approvedSales = (int) SellerTransaction::query()
                    ->where('seller_id', $storeSellerId)
                    ->where('status', SellerTransaction::STATUS_APPROVED)
                    ->where('category', 'order_sale')
                    ->sum('netAmount');

                $approvedReversals = (int) SellerTransaction::query()
                    ->where('seller_id', $storeSellerId)
                    ->where('status', SellerTransaction::STATUS_APPROVED)
                    ->where('category', 'order_reversal')
                    ->sum('netAmount');

                $totalIncome = max(0, $approvedSales - $approvedReversals);
                $withdrawableBalance = max(0, (int) ($storeSeller->balance ?? 0));
                $totalWithdrawal = max(0, (int) ($storeSeller->total_withdrawal ?? 0));

                $productScore = round(max(45, min(100, ($productRating / 5) * 100)), 2);
                $responseHours = max(0.25, (float) ($metrics['response_time_hours'] ?? $storeSeller->response_time_hours ?? 24));
                $responseScore = match (true) {
                    $responseHours <= 1 => 100.0,
                    $responseHours <= 3 => 95.0,
                    $responseHours <= 6 => 90.0,
                    $responseHours <= 12 => 82.0,
                    $responseHours <= 24 => 72.0,
                    $responseHours <= 48 => 58.0,
                    default => 45.0,
                };
                $successScore = round(((float) ($metrics['success_rate'] ?? 0.78)) * 100, 2);
                $catalogHealth = ($bookCount + $stationeryCount) > 0
                    ? round((($activeBooks + $activeStationery) / ($bookCount + $stationeryCount)) * 100, 2)
                    : 70.0;

                $karma = round(
                    ($reputation['reputation_score'] * 0.46) +
                    ($productScore * 0.24) +
                    ($responseScore * 0.18) +
                    ($catalogHealth * 0.07) +
                    ($successScore * 0.05),
                    2
                );

                $tier = match (true) {
                    $karma >= 90 => 'Top store',
                    $karma >= 82 => 'Strong store',
                    $karma >= 72 => 'Stable store',
                    $karma >= 60 => 'Growing store',
                    default => 'Needs focus',
                };

                return [
                    'karma' => $karma,
                    'tier' => $tier,
                    'public_rating' => round((float) ($reputation['rating'] ?? $storeSeller->rating ?? 5), 2),
                    'reputation_score' => round((float) ($reputation['reputation_score'] ?? $storeSeller->reputation_score ?? 78), 2),
                    'product_rating' => $productRating,
                    'product_score' => $productScore,
                    'response_time_hours' => round($responseHours, 2),
                    'response_score' => $responseScore,
                    'success_score' => $successScore,
                    'catalog_health' => $catalogHealth,
                    'completed_orders' => (int) ($metrics['completed_all'] ?? 0),
                    'completed_30d' => (int) ($metrics['completed_30d'] ?? 0),
                    'cancelled_90d' => (int) ($metrics['cancelled_90d'] ?? 0),
                    'returned_90d' => (int) ($metrics['returned_90d'] ?? 0),
                    'active_products' => $activeBooks + $activeStationery,
                    'all_products' => $bookCount + $stationeryCount,
                    'sold_products' => $soldProducts,
                    'returned_products' => $returnedProducts,
                    'total_income' => $totalIncome,
                    'withdrawable_balance' => $withdrawableBalance,
                    'total_withdrawal' => $totalWithdrawal,
                ];
            }
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Ruxsatsiz'], 401);
        }

        // ✅ FAQAT OWNER
        if (!$this->hasOwnerAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Only Owner can update profile.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'shop_name' => 'required|string|max:55',
            'first_name' => 'nullable|string|max:40',
            'last_name' => 'nullable|string|max:40',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $validator->errors(),
            ], 422);
        }

        $photoPath = $seller->photo;
        if ($request->hasFile('images') && $request->file('images')[0]->isValid()) {
            try {
                if ($photoPath) {
                    Storage::disk('public')->delete($photoPath);
                }
                $image = $request->file('images')[0];
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $path = Storage::disk('public')->putFileAs('seller_photos', $image, $filename);
                $photoPath = str_replace('public/', '', $path);
            } catch (\Exception $e) {
                Log::error('Rasmni saqlashda xato', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Rasmni saqlash muvaffaqiyatsiz',
                ], 500);
            }
        }

        $seller->update([
            'shop_name' => $request->shop_name,
            'firstname' => $request->first_name,
            'lastname' => $request->last_name,
            'photo' => $photoPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profil muvaffaqiyatli yangilandi',
            'photo' => $photoPath,
        ], 200);
    }

    public function updatePassword(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $this->passwordResetService->ensureHasAttempts($seller);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 429);
        }

        $randomPassword = $this->passwordResetService->generatePassword(12);

        try {
            $remaining = $this->passwordResetService->applyNewPassword($seller, $randomPassword);

            return response()->json([
                'success' => true,
                'message' => 'Parol muvaffaqiyatli yangilandi',
                'new_password' => $randomPassword, // SMS uchun
                'remaining_attempts' => $remaining,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Parolni yangilashda xato', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Parolni yangilashda xatolik yuz berdi'
            ], 500);
        }
    }
    
    public function updateFcm(Request $request)
{
    $seller = Auth::guard('seller')->user();

    $request->validate([
        'fcm_token' => 'required|string',
        'device_id' => 'required|string',
    ]);

    $updated = DB::table('connected_devices')
        ->where('user_id', $seller->id)
        ->where('user_type', 'seller')
        ->where('device_id', $request->device_id)
        ->update([
            'fcm_token'  => $request->fcm_token,
            'updated_at' => now(),
        ]);

    if ($updated) {
        return response()->json([
            'status'  => 'success',
            'message' => "Bildirishnoma manzili yangilandi",
        ]);
    }
    DB::table('connected_devices')->updateOrInsert(
        [
            'user_id' => $seller->id,
            'user_type' => 'seller',
            'device_id' => $request->device_id,
        ],
        [
            'token' => optional($seller->currentAccessToken())->token,
            'fcm_token' => $request->fcm_token,
            'device_name' => $request->input('device_name', 'Unknown Device'),
            'platform' => $request->input('platform', 'Unknown'),
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    return response()->json([
        'status'  => 'success',
        'message' => "Qurilma yaratildi va token saqlandi",
    ]);
}
}
