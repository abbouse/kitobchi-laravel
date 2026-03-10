<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Sold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Foydalanuvchilar ro'yxati
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->with(['location']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name',         'like', "%{$search}%")
                  ->orWhere('lastname',   'like', "%{$search}%")
                  ->orWhere('phone_number','like', "%{$search}%")
                  ->orWhere('email',      'like', "%{$search}%")
                  ->orWhere('id',         $search);
            });
        }

        // Filter
        switch ($request->get('filter')) {
            case 'premium':  $query->where('is_premium', true)->where('premium_until', '>', now()); break;
            case 'verified': $query->where('isVerified', true);  break;
            case 'support':  $query->where('isSupport', true);   break;
            case 'deleted':  $query->where('isDeleted', 'yes');  break;
            default:         $query->where(function($q) { $q->where('isDeleted', 'no')->orWhereNull('isDeleted'); }); break;
        }

        // Sort
        switch ($request->get('sort', 'latest')) {
            case 'oldest':  $query->oldest();                       break;
            case 'balance': $query->orderByDesc('real_balance');    break;
            case 'name':    $query->orderBy('name');                break;
            default:        $query->latest();                       break;
        }

        $users = $query->paginate(20);

        // Stats
        $stats = [
            'total'    => User::where(function($q) { $q->where('isDeleted', 'no')->orWhereNull('isDeleted'); })->count(),
            'premium'  => User::where('is_premium', true)->where('premium_until', '>', now())->count(),
            'verified' => User::where('isVerified', true)->count(),
            'today'    => User::whereDate('created_at', today())->count(),
        ];

        return view('pages.users.index', compact('users', 'stats'));
    }

    /**
     * Foydalanuvchi batafsil sahifasi
     */
    public function show(User $user)
    {
        $user->load([
            'location',
            'cart.product',
            'cart.variant',
            'cards',
        ]);

        // Buyurtmalar (Sold table)
        $orders = Sold::where('user_id', $user->id)
            ->latest()
            ->get();

        return view('pages.users.show', compact('user', 'orders'));
    }

    /**
     * Tahrirlash formasi
     */
    public function edit(User $user)
    {
        return view('pages.users.edit', compact('user'));
    }

    /**
     * Yangilash
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:30',
            'lastname'      => 'required|string|max:30',
            'phone_number'  => 'nullable|string|max:12',
            'email'         => 'nullable|email|max:40|unique:users,email,' . $user->id,
            'sex'           => 'nullable|in:M,F',
            'locale'        => 'nullable|in:uz,ru,en,ja',
            'position'      => 'nullable|string|max:40',
            'status'        => 'nullable|string|max:300',
            'real_balance'  => 'nullable|integer|min:0',
            'cashback'      => 'nullable|integer|min:0',
            'ai_limit'      => 'nullable|integer|min:0|max:1000',
            'is_premium'    => 'nullable|boolean',
            'premium_until' => 'nullable|date',
            'isVerified'    => 'nullable|boolean',
            'isSupport'     => 'nullable|boolean',
            'isDeleted'     => 'nullable|in:yes,no',
            'avatar'        => 'nullable|image|max:2048',
        ]);

        // Avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = Storage::url($path);
        }

        // Checkbox booleans
        $validated['is_premium'] = $request->boolean('is_premium');
        $validated['isVerified'] = $request->boolean('isVerified');
        $validated['isSupport']  = $request->boolean('isSupport');

        $user->update($validated);

        return redirect()
            ->route('admin.users.show', $user->id)
            ->with('success', "Foydalanuvchi ma'lumotlari muvaffaqiyatli yangilandi.");
    }
}