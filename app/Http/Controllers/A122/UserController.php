<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\ConnectedDevice;
use App\Models\GiftCertificate;
use App\Models\MysteryBoxSubscription;
use App\Models\Sold;
use App\Models\UserCard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        $buyersQuery = Sold::query()->select('user_id')->whereNotNull('user_id')->distinct();
        $tab = $request->input('tab', 'all');

        match ($tab) {
            'active' => $query->where('isVerified', true),
            'pending' => $query->where(fn ($inner) => $inner->where('isVerified', false)->orWhereNull('isVerified')),
            'premium' => $query->where('is_premium', true),
            'buyers' => $query->whereIn('id', $buyersQuery),
            'blocked' => $query->where('status', 'blocked')
                ->where(fn ($inner) => $inner->whereNull('blocked_until')->orWhere('blocked_until', '>', now())),
            default => null,
        };

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('lastname', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('id', $search));
        }

        $users = $query->latest()->paginate(20)->withQueryString();
        $counts = [
            'all' => User::count(),
            'active' => User::where('isVerified', true)->count(),
            'pending' => User::where(fn ($inner) => $inner->where('isVerified', false)->orWhereNull('isVerified'))->count(),
            'premium' => User::where('is_premium', true)->count(),
            'buyers' => (clone $buyersQuery)->count(),
            'blocked' => User::where('status', 'blocked')
                ->where(fn ($inner) => $inner->whereNull('blocked_until')->orWhere('blocked_until', '>', now()))
                ->count(),
        ];

        $rows = $users->map(function (User $u) {
            return [
                'id' => $u->id,
                'name' => trim(($u->name ?? '').' '.($u->lastname ?? '')),
                'email' => $u->email ?: '—',
                'role' => $u->position ?: 'User',
                'status' => $u->isBlocked() ? 'blocked' : ($u->isVerified ? 'active' : 'pending'),
                'orders' => 0,
                'joined' => optional($u->created_at)->format('Y-m-d'),
                'avatar' => $u->avatar ? asset('storage/'.$u->avatar) : null,
            ];
        })->values();

        return view('a122.users.index', compact('users', 'rows', 'counts', 'tab'));
    }

    public function create()
    {
        return view('a122.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'lastname' => 'nullable|string|max:100',
            'phone_number' => 'required|string|unique:users,phone_number',
            'email' => 'nullable|email|unique:users,email',
            'position' => 'nullable|string|max:100',
            'isVerified' => 'boolean',
            'is_premium' => 'boolean',
        ]);

        $data['password'] = Hash::make('12345678');
        $user = User::create($data);

        return redirect()->route('admin.users.show', $user)->with('success', 'Foydalanuvchi yaratildi.');
    }

    public function show(User $user)
    {
        if ($user->status === 'blocked' && $user->blocked_until && $user->blocked_until->isPast()) {
            $user->update([
                'status' => $user->isVerified ? 'active' : 'pending',
                'blocked_until' => null,
                'blocked_at' => null,
                'block_reason' => null,
                'blocked_by_admin_id' => null,
            ]);
            $user->refresh();
        }

        $user->loadCount(['cards', 'devices', 'followers', 'followings']);

        $ordersQuery = Sold::query()->where('user_id', $user->id);
        $giftCertificatesQuery = GiftCertificate::query()
            ->where(fn ($q) => $q
                ->where('buyer_user_id', $user->id)
                ->orWhere('recipient_user_id', $user->id));

        $recentOrders = (clone $ordersQuery)
            ->latest()
            ->take(8)
            ->get();

        $cards = UserCard::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        $devices = ConnectedDevice::query()
            ->where('user_id', $user->id)
            ->where('user_type', 'user')
            ->latest()
            ->take(8)
            ->get();

        $addresses = DB::table('locations')
            ->where('user_id', $user->id)
            ->where('isDeleted', false)
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        $followers = DB::table('user_follows')
            ->where('following_id', $user->id)
            ->join('users', 'users.id', '=', 'user_follows.follower_id')
            ->select('users.id', 'users.name', 'users.lastname', 'users.avatar', 'users.phone_number')
            ->latest('user_follows.created_at')
            ->take(8)
            ->get();

        $following = DB::table('user_follows')
            ->where('follower_id', $user->id)
            ->join('users', 'users.id', '=', 'user_follows.following_id')
            ->select('users.id', 'users.name', 'users.lastname', 'users.avatar', 'users.phone_number')
            ->latest('user_follows.created_at')
            ->take(8)
            ->get();

        $giftCertificates = (clone $giftCertificatesQuery)
            ->latest()
            ->take(8)
            ->get();

        $mysterySubscriptions = MysteryBoxSubscription::query()
            ->with(['plan:id,name_uz,months,books_per_month', 'deliveries'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(6)
            ->get();

        $stats = [
            'orders_count' => (clone $ordersQuery)->count(),
            'paid_orders_count' => (clone $ordersQuery)->where('paymentStatus', 2)->count(),
            'total_spent' => (float) (clone $ordersQuery)->where('paymentStatus', 2)->sum('amount'),
            'cards_count' => $user->cards_count ?? 0,
            'devices_count' => $user->devices_count ?? 0,
            'addresses_count' => $addresses->count(),
            'followers_count' => $user->followers_count ?? 0,
            'following_count' => $user->followings_count ?? 0,
            'gift_certificates_count' => (clone $giftCertificatesQuery)->count(),
            'mystery_subscriptions_count' => $mysterySubscriptions->count(),
        ];

        return view('a122.users.show', compact(
            'user',
            'stats',
            'recentOrders',
            'cards',
            'devices',
            'addresses',
            'followers',
            'following',
            'giftCertificates',
            'mysterySubscriptions'
        ));
    }

    public function edit(User $user)
    {
        return view('a122.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'lastname' => 'nullable|string|max:100',
            'phone_number' => ['required', 'string', Rule::unique('users', 'phone_number')->ignore($user->id)],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'position' => 'nullable|string|max:100',
            'isVerified' => 'boolean',
            'is_premium' => 'boolean',
        ]);

        $user->update($data);

        return redirect()->route('admin.users.show', $user)->with('success', "Ma'lumotlar yangilandi.");
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', "Foydalanuvchi o'chirildi.");
    }

    public function toggleVerify(User $user)
    {
        $user->update(['isVerified' => ! $user->isVerified]);

        return back()->with('success', $user->isVerified ? 'Foydalanuvchi tasdiqlandi.' : 'Tasdiq bekor qilindi.');
    }

    public function togglePremium(User $user)
    {
        $next = ! (bool) $user->is_premium;
        $user->update([
            'is_premium' => $next,
            'premium_until' => $next ? now()->addMonth() : null,
        ]);

        return back()->with('success', $next ? 'Premium yoqildi.' : 'Premium o‘chirildi.');
    }

    public function block(Request $request, User $user)
    {
        $data = $request->validate([
            'block_period' => 'required|in:10_days,1_month,1_year,3_years,forever',
            'block_reason' => 'required|string|max:5000',
        ]);

        $blockedUntil = match ($data['block_period']) {
            '10_days' => now()->addDays(10),
            '1_month' => now()->addMonth(),
            '1_year' => now()->addYear(),
            '3_years' => now()->addYears(3),
            'forever' => null,
        };

        $user->forceFill([
            'status' => 'blocked',
            'blocked_until' => $blockedUntil,
            'blocked_at' => now(),
            'block_reason' => trim($data['block_reason']),
            'blocked_by_admin_id' => auth()->id(),
        ])->save();

        $user->tokens()->delete();
        ConnectedDevice::where('user_id', $user->id)->where('user_type', 'user')->delete();

        return back()->with('success', 'Foydalanuvchi bloklandi.');
    }

    public function unblock(User $user)
    {
        $user->forceFill([
            'status' => $user->isVerified ? 'active' : 'pending',
            'blocked_until' => null,
            'blocked_at' => null,
            'block_reason' => null,
            'blocked_by_admin_id' => null,
        ])->save();

        return back()->with('success', 'Foydalanuvchi blokdan chiqarildi.');
    }
}
