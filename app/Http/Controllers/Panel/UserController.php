<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BookClub;
use App\Models\MyCart;
use App\Models\GiftCertificate;
use App\Models\MysteryBoxSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use App\Imports\UsersImport;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(fn($q) => $q
                ->where('name',          'like', "%{$search}%")
                ->orWhere('lastname',    'like', "%{$search}%")
                ->orWhere('phone_number','like', "%{$search}%")
                ->orWhere('email',       'like', "%{$search}%")
                ->orWhere('id', $search)
            );
        }

        if ($request->filled('is_premium')) $query->where('is_premium', $request->boolean('is_premium'));
        if ($request->filled('isVerified')) $query->where('isVerified', $request->boolean('isVerified'));
        if ($request->filled('status'))     $query->where('status', $request->input('status'));

        $sortBy  = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'desc');
        if (in_array($sortBy, ['id','name','phone_number','created_at','real_balance'])) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $users = $query->paginate(20)->withQueryString();

        $stats = [
            'total'   => User::count(),
            'premium' => User::where('is_premium', true)->count(),
            'online'  => User::where('last_seen_at', '>=', now()->subMinutes(5))->count(),
            'today'   => User::whereDate('created_at', today())->count(),
        ];

        return view('panel.users.index', compact('users', 'stats'));
    }

    public function create()
    {
        return view('panel.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'lastname'     => 'nullable|string|max:100',
            'phone_number' => 'required|string|unique:users,phone_number',
            'email'        => 'nullable|email|unique:users,email',
            'position'     => 'nullable|string|max:100',
            'is_premium'   => 'boolean',
            'isVerified'   => 'boolean',
            'real_balance' => 'nullable|numeric|min:0',
            'avatar'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }
        $data['password'] = Hash::make('12345678');

        $user = User::create($data);
        return redirect()->route('panel.users.show', $user)
            ->with('success', "Foydalanuvchi yaratildi.");
    }

    public function show(User $user, Request $request)
    {
        // ── Qurilmalar (connected_devices) ────────────────────────
        $devices = DB::table('connected_devices')
            ->where('user_id', $user->id)
            ->where('user_type', 'user')
            ->orderByDesc('created_at')
            ->get();

        // ── Manzillar (locations jadval) ──────────────────────────
        $addresses = DB::table('locations')
            ->where('user_id', $user->id)
            ->where('isDeleted', false)
            ->orderByDesc('created_at')
            ->get();

        // ── Bank kartalar ─────────────────────────────────────────
        $cards = \App\Models\UserCard::where('user_id', $user->id)
            ->where('is_verified', true)
            ->orderByDesc('created_at')
            ->get(['id', 'card_number', 'created_at']);

        // ── Savat ─────────────────────────────────────────────────
        $rawCart = MyCart::where('user_id', $user->id)
            ->with(['product', 'variant'])
            ->get();

        $cartItems = $rawCart->map(function ($item) {
            $images = $item->product?->images ?? [];
            if (is_string($images)) $images = json_decode($images, true) ?? [];
            $image  = is_array($images) && count($images) ? $images[0] : null;
            $price  = $item->productPrice; // accessor

            return (object) [
                'id'           => $item->id,
                'product_id'   => $item->product_id,
                'product_type' => $item->product_type,
                'name'         => $item->product?->name ?? 'Noma\'lum',
                'image'        => $image,
                'price'        => $price,
                'count'        => $item->count_item,
                'subtotal'     => $price * $item->count_item,
                'variant'      => $item->variant?->name ?? null,
                'route'        => $item->product_type === 'book'
                    ? route('panel.books.show', $item->product_id)
                    : route('panel.stationery.show', $item->product_id),
            ];
        });

        $cartTotal = $cartItems->sum('subtotal');

        // ── Xaridlar ──────────────────────────────────────────────
        $orderCount   = \App\Models\Sold::where('user_id', $user->id)->count();
        $totalSpent   = \App\Models\Sold::where('user_id', $user->id)
            ->where('paymentStatus', 2)->sum('amount');
        $recentOrders = \App\Models\Sold::where('user_id', $user->id)
            ->latest()->take(5)->get();

        // ── Ijtimoiy ──────────────────────────────────────────────
        $followersCount = DB::table('user_follows')
            ->where('following_id', $user->id)->count();
        $followingCount = DB::table('user_follows')
            ->where('follower_id', $user->id)->count();

        $followers = DB::table('user_follows')
            ->where('following_id', $user->id)
            ->join('users', 'users.id', '=', 'user_follows.follower_id')
            ->select('users.id', 'users.name', 'users.lastname', 'users.avatar')
            ->latest('user_follows.created_at')->take(8)->get();

        $following = DB::table('user_follows')
            ->where('follower_id', $user->id)
            ->join('users', 'users.id', '=', 'user_follows.following_id')
            ->select('users.id', 'users.name', 'users.lastname', 'users.avatar')
            ->latest('user_follows.created_at')->take(8)->get();

        // ── Book Club ─────────────────────────────────────────────
        $tab = $request->get('bc_tab', 'posts');

        $bcQuery = BookClub::with(['images', 'votes'])
            ->where('user_id', $user->id)
            ->where('is_deleted', false)
            ->withCount(['likes', 'comments']);

        $bcPostsCount   = (clone $bcQuery)->where('repost', false)->count();
        $bcRepostsCount = (clone $bcQuery)->where('repost', true)->count();

        $bcQuery->where('repost', $tab === 'reposts');

        $bcPosts = $bcQuery->latest()
            ->paginate(6, ['*'], 'bc_page')
            ->withQueryString();

        // ── Gift sertifikatlar (sotib olgan + qabul qilgan) ──────────
        $giftCerts = \App\Models\GiftCertificate::with([
                'buyer:id,name,lastname',
                'recipient:id,name,lastname',
            ])
            ->where(function ($q) use ($user) {
                $q->where('buyer_user_id', $user->id)
                  ->orWhere('recipient_user_id', $user->id);
            })
            ->latest()
            ->get();
        $giftCertCount = $giftCerts->count();

        // ── Mystery Box obunalari ─────────────────────────────────────
        $mysterySubs = \App\Models\MysteryBoxSubscription::with([
                'plan:id,name_uz,months,books_per_month',
                'deliveries',
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();
        $mysterySubCount = $mysterySubs->count();

        return view('panel.users.show', compact(
            'user', 'devices', 'addresses', 'cards',
            'cartItems', 'cartTotal',
            'orderCount', 'totalSpent', 'recentOrders',
            'followersCount', 'followingCount', 'followers', 'following',
            'giftCerts', 'giftCertCount',
            'mysterySubs', 'mysterySubCount',
            'bcPosts', 'bcPostsCount', 'bcRepostsCount', 'tab'
        ));
    }

    public function edit(User $user)
    {
        return view('panel.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'lastname'     => 'nullable|string|max:100',
            'phone_number' => ['required','string',
                Rule::unique('users','phone_number')->ignore($user->id)],
            'email'        => ['nullable','email',
                Rule::unique('users','email')->ignore($user->id)],
            'position'     => 'nullable|string|max:100',
            'bio'          => 'nullable|string|max:500',
            'is_premium'   => 'boolean',
            'isVerified'   => 'boolean',
            'real_balance' => 'nullable|numeric|min:0',
            'cashback'     => 'nullable|numeric|min:0',
            'ai_limit'     => 'nullable|integer|min:0',
            'avatar'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        if ($request->boolean('is_premium') && empty($data['premium_until'])) {
            $data['premium_until'] = now()->addYear();
        }

        $user->update($data);
        return redirect()->route('panel.users.show', $user)
            ->with('success', "Ma'lumotlar yangilandi.");
    }

    public function destroy(User $user)
    {
        if ($user->avatar) Storage::disk('public')->delete($user->avatar);
        $user->delete();
        return redirect()->route('panel.users.index')
            ->with('success', "Foydalanuvchi o'chirildi.");
    }

    // ── Bank kartani o'chirish (admin panel) ──────────────────
    public function destroyCard(User $user, \App\Models\UserCard $card)
    {
        // Karta shu userga tegishligini tekshirish
        abort_if($card->user_id !== $user->id, 403);

        // Payme tokenini o'chirish (ixtiyoriy — xatolik bo'lsa ham davom etamiz)
        try {
            app(\App\Services\PaymeService::class)->request('cards.remove', [
                'token' => $card->payme_token,
            ]);
        } catch (\Throwable $e) {
            // Payme xatosi panelni to'xtatmasin
        }

        $card->delete();

        return back()->with('success',
            'Karta ****'.substr($card->card_number, -4).' o\'chirildi.');
    }

    public function togglePremium(User $user)
    {
        if ($user->is_premium) {
            $user->update(['is_premium' => false, 'premium_until' => null]);
            $msg = "Premium olib tashlandi.";
        } else {
            $user->update(['is_premium' => true, 'premium_until' => now()->addYear()]);
            $msg = "Premium berildi (1 yil).";
        }
        return back()->with('success', $msg);
    }

    public function export(Request $request)
    {
        return Excel::download(
            new UsersExport($request->all()),
            'users_' . now()->format('Y-m-d_H-i') . '.xlsx'
        );
    }

    public function importView()
    {
        return view('panel.users.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);
        try {
            Excel::import(new UsersImport, $request->file('file'));
            return redirect()->route('panel.users.index')
                ->with('success', "Import muvaffaqiyatli.");
        } catch (\Exception $e) {
            return back()->with('error', "Import xatosi: " . $e->getMessage());
        }
    }
}