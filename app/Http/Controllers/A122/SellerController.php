<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerBanLog;
use App\Models\SellerOrder;
use App\Models\SellerStaffLog;
use App\Models\SellerTransaction;
use App\Services\PasswordResetService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

class SellerController extends Controller
{
    public function __construct(
        private readonly SmsService $smsService,
        private readonly PasswordResetService $passwordResetService
    ) {
    }

    private function mainScope($query)
    {
        return $query->where(fn($q) => $q->whereNull('parent_id')->orWhere('parent_id', 0));
    }

    private function resolveStoreSeller(Seller $seller): Seller
    {
        if (!$seller->parent_id) {
            return $seller;
        }

        return Seller::query()->findOrFail($seller->parent_id);
    }

    public function index(Request $request)
    {
        $query = Seller::withCount(['books', 'orders'])
            ->withCount([
                'premiumSubscriptions',
            ]);
        $this->mainScope($query);

        $tab = $request->input('tab', 'pending');
        if ($tab !== 'all') $query->where('status', $tab);

        if ($s = $request->input('search')) {
            $query->where(fn($q) => $q
                ->where('shop_name', 'like', "%$s%")
                ->orWhere('firstname', 'like', "%$s%")
                ->orWhere('lastname', 'like', "%$s%")
                ->orWhere('phone_number', 'like', "%$s%")
                ->orWhere('region', 'like', "%$s%")
                ->orWhere('id', $s)
            );
        }

        $sellers = $query->latest()->paginate(20)->withQueryString();
        $sellers->getCollection()->transform(function (Seller $seller) {
            $seller->active_warning_count = SellerBanLog::getWarningCount($seller->id);
            return $seller;
        });

        $base = fn() => Seller::where(fn($q) => $q->whereNull('parent_id')->orWhere('parent_id', 0));
        $counts = [
            'pending'  => $base()->where('status', 'pending')->count(),
            'approved' => $base()->where('status', 'approved')->count(),
            'rejected' => $base()->where('status', 'rejected')->count(),
            'blocked'  => $base()->where('status', 'blocked')->count(),
            'all'      => $base()->count(),
        ];

        return view('a122.sellers.index', compact('sellers', 'counts', 'tab'));
    }

    public function show(Seller $seller)
    {
        $storeSeller = $this->resolveStoreSeller($seller);
        $seller->loadCount('books')->load(['books', 'location']);
        $storeSellerId = $storeSeller->id;
        $sellerIds = Seller::where('id', $storeSellerId)
            ->orWhere('parent_id', $storeSellerId)
            ->pluck('id');
        $orderCount   = SellerOrder::where('seller_id', $seller->id)->count();
        $totalRevenue = SellerTransaction::where('seller_id', $seller->id)->where('status', 'approved')->sum('amount');
        $recentOrders = SellerOrder::where('seller_id', $seller->id)->latest()->take(8)->get();
        $transactions = SellerTransaction::where('seller_id', $seller->id)->latest()->take(8)->get();
        $staffLogs = SellerStaffLog::whereIn('seller_staff_id', $sellerIds)
            ->latest()
            ->take(50)
            ->get();
        $banLogs = SellerBanLog::where('seller_id', $storeSellerId)
            ->latest()
            ->take(50)
            ->get();
        $warningCount = SellerBanLog::getWarningCount($storeSellerId);
        $isBlocked = $storeSeller->status === 'blocked';

        return view('a122.sellers.show', compact('seller', 'storeSeller', 'orderCount', 'totalRevenue', 'recentOrders', 'transactions', 'staffLogs', 'banLogs', 'warningCount', 'isBlocked'));
    }

    public function edit(Seller $seller)
    {
        return view('a122.sellers.edit', compact('seller'));
    }

    public function update(Request $request, Seller $seller)
    {
        $data = $request->validate([
            'shop_name'    => 'required|string|max:255',
            'firstname'    => 'nullable|string|max:100',
            'lastname'     => 'nullable|string|max:100',
            'phone_number' => ['required', 'string', Rule::unique('sellers', 'phone_number')->ignore($seller->id)],
            'region'       => 'required|string|max:100',
            'status'       => 'required|in:pending,approved,rejected,blocked',
            'balance'      => 'nullable|numeric|min:0',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        if ($request->hasFile('photo')) {
            if ($seller->photo) Storage::disk('public')->delete($seller->photo);
            $data['photo'] = $request->file('photo')->store('seller_photos', 'public');
        }

        $seller->update($data);
        return redirect()->route('admin.sellers.show', $seller)->with('success', "Ma'lumotlar yangilandi.");
    }

    public function approve(Seller $seller)
    {
        $seller->update(['status' => 'approved']);
        return back()->with('success', 'Sotuvchi tasdiqlandi.');
    }

    public function reject(Seller $seller)
    {
        $seller->update(['status' => 'rejected']);
        return back()->with('success', 'Sotuvchi rad etildi.');
    }

    public function warn(Request $request, Seller $seller)
    {
        $request->validate([
            'title' => 'required|string|max:120',
            'message' => 'required|string|max:2000',
        ]);

        $storeSeller = $this->resolveStoreSeller($seller);

        DB::transaction(function () use ($request, $storeSeller) {
            SellerBanLog::create([
                'seller_id' => $storeSeller->id,
                'title' => $request->string('title')->toString(),
                'message' => $request->string('message')->toString(),
                'type' => SellerBanLog::TYPE_WARNING,
                'is_read' => false,
            ]);

            if (SellerBanLog::hasReachedBlockThreshold($storeSeller->id)) {
                $storeSeller->update(['status' => 'blocked']);
            }
        });

        $warningCount = SellerBanLog::getWarningCount($storeSeller->id);
        $message = $warningCount >= 3
            ? 'Ogohlantirish yuborildi va sotuvchi avtomatik bloklandi.'
            : "Ogohlantirish yuborildi. Faol ogohlantirishlar soni: {$warningCount}";

        return back()->with('success', $message);
    }

    public function unblock(Request $request, Seller $seller)
    {
        $request->validate([
            'message' => 'nullable|string|max:2000',
        ]);

        $storeSeller = $this->resolveStoreSeller($seller);

        DB::transaction(function () use ($request, $storeSeller) {
            $storeSeller->update(['status' => 'approved']);

            SellerBanLog::create([
                'seller_id' => $storeSeller->id,
                'title' => 'Blokdan chiqarildi',
                'message' => $request->filled('message')
                    ? $request->string('message')->toString()
                    : 'Admin tomonidan blokdan chiqarildi. Ogohlantirish hisobi qayta boshlandi.',
                'type' => SellerBanLog::TYPE_UNBAN,
                'is_read' => false,
            ]);
        });

        return back()->with('success', 'Sotuvchi blokdan chiqarildi.');
    }

    public function resetPassword(Seller $seller)
    {
        if ($seller->status !== 'approved') {
            return back()->with('error', 'Faqat tasdiqlangan sotuvchi uchun parolni yangilash mumkin.');
        }

        try {
            $this->passwordResetService->ensureHasAttempts($seller);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        try {
            $newPassword = $this->passwordResetService->generatePassword();
            $this->smsService->send(
                $seller->phone_number,
                "Kitobchi Business: sizning yangi parolingiz — {$newPassword}"
            );
            $remaining = $this->passwordResetService->applyNewPassword($seller, $newPassword);

            return back()->with('success', "Yangi parol SMS orqali yuborildi. Qolgan urinishlar: {$remaining}");
        } catch (\Throwable $e) {
            return back()->with('error', 'Parolni SMS orqali yuborishda xatolik yuz berdi.');
        }
    }
}
