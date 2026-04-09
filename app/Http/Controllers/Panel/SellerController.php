<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerTransaction;
use App\Models\SellerLocation;
use App\Models\SellerBanLog;
use App\Models\SellerAd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SellersExport;

class SellerController extends Controller
{
    const ROLES = [
        'admin'            => 'Administrator',
        'product_manager'  => 'Mahsulot boshqaruvchisi',
        'customer_service' => "Mijozlar bo'limi",
        'accountant'       => 'Buxgalter',
    ];

    private function isMain(Seller $seller): bool
    {
        return is_null($seller->parent_id) || $seller->parent_id == 0;
    }

    private function mainScope($query)
    {
        return $query->where(fn($q) =>
            $q->whereNull('parent_id')->orWhere('parent_id', 0)
        );
    }

    // ── INDEX ──────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Seller::withCount(['books', 'stationeries', 'orders']);
        $this->mainScope($query);

        $tab = $request->input('tab', 'pending');
        if ($tab !== 'all') $query->where('status', $tab);

        if ($s = $request->input('search')) {
            $query->where(fn($q) => $q
                ->where('shop_name',      'like', "%$s%")
                ->orWhere('firstname',    'like', "%$s%")
                ->orWhere('phone_number', 'like', "%$s%")
                ->orWhere('id', $s)
            );
        }

        if ($request->filled('region')) {
            $query->where('region', $request->input('region'));
        }

        $sellers = $query->latest()->paginate(20)->withQueryString();

        $base = fn() => Seller::where(fn($q) => $q->whereNull('parent_id')->orWhere('parent_id', 0));
        $counts = [
            'pending'  => $base()->where('status', 'pending')->count(),
            'approved' => $base()->where('status', 'approved')->count(),
            'rejected' => $base()->where('status', 'rejected')->count(),
            'all'      => $base()->count(),
        ];

        $regions = $base()->distinct()->pluck('region')->filter()->sort()->values();

        return view('panel.sellers.index', compact('sellers', 'counts', 'tab', 'regions'));
    }

    // ── SHOW ───────────────────────────────────────────────────
    public function show(Seller $seller)
    {
        $seller->load(['books', 'stationeries', 'location']);

        $isMainShop   = $this->isMain($seller);
        $orderCount   = SellerOrder::where('seller_id', $seller->id)->count();
        $totalRevenue = SellerTransaction::where('seller_id', $seller->id)
            ->where('status', 'approved')->sum('amount');
        $recentOrders = SellerOrder::where('seller_id', $seller->id)
            ->latest()->take(8)->get();
        $transactions = SellerTransaction::where('seller_id', $seller->id)
            ->latest()->take(8)->get();

        $staff = $isMainShop
            ? Seller::where('parent_id', $seller->id)->orderBy('created_at')->get()
            : collect();

        $parentShop = !$isMainShop ? Seller::find($seller->parent_id) : null;

        $locations = SellerLocation::where('seller_id', $seller->id)
            ->where('is_deleted', false)->get();

        $banLogs = SellerBanLog::where('seller_id', $seller->id)
            ->latest()->take(10)->get();

        $ads = SellerAd::where('seller_id', $seller->id)
            ->latest()->take(6)->get();

        return view('panel.sellers.show', compact(
            'seller', 'isMainShop', 'staff', 'parentShop',
            'orderCount', 'totalRevenue', 'recentOrders', 'transactions',
            'locations', 'banLogs', 'ads'
        ));
    }

    // ── EDIT ───────────────────────────────────────────────────
    public function edit(Seller $seller)
    {
        $isMainShop = $this->isMain($seller);
        $roles      = self::ROLES;
        return view('panel.sellers.edit', compact('seller', 'isMainShop', 'roles'));
    }

    public function update(Request $request, Seller $seller)
    {
        $isMainShop = $this->isMain($seller);

        $rules = [
            'shop_name'        => 'required|string|max:255',
            'firstname'        => 'nullable|string|max:100',
            'lastname'         => 'nullable|string|max:100',
            'phone_number'     => ['required', 'string',
                Rule::unique('sellers', 'phone_number')->ignore($seller->id)],
            'region'           => 'required|string|max:100',
            'status'           => 'required|in:pending,approved,rejected',
            'is_hidden'        => 'boolean',
            'balance'          => 'nullable|numeric|min:0',
            'photo'            => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'activity_types'      => 'nullable|array',
            'activity_types.*'    => 'in:Kitob,Kanstovar',
            'commission_percent'  => 'nullable|numeric|min:0|max:100',
        ];

        if (!$isMainShop) {
            $rules['staff_status'] = 'required|in:active,inactive';
            $rules['role']         = 'nullable|in:' . implode(',', array_keys(self::ROLES));
        }

        $data = $request->validate($rules);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        if ($request->hasFile('photo')) {
            if ($seller->photo) Storage::disk('public')->delete($seller->photo);
            $data['photo'] = $request->file('photo')->store('seller_photos', 'public');
        }

        // activity_types — setActivityTypesAttribute() handle qiladi
        $seller->update($data);

        return redirect()->route('panel.sellers.show', $seller)
            ->with('success', "Ma'lumotlar yangilandi.");
    }

    // ── HODIM YARATISH ─────────────────────────────────────────
    public function createStaff(Seller $seller)
    {
        abort_unless($this->isMain($seller), 403);
        $roles = self::ROLES;
        return view('panel.sellers.create-staff', compact('seller', 'roles'));
    }

    public function storeStaff(Request $request, Seller $seller)
    {
        abort_unless($this->isMain($seller), 403);

        $data = $request->validate([
            'firstname'    => 'required|string|max:100',
            'lastname'     => 'nullable|string|max:100',
            'phone_number' => 'required|string|unique:sellers,phone_number',
            'password'     => 'required|string|min:6',
            'role'         => 'required|in:' . implode(',', array_keys(self::ROLES)),
            'staff_status' => 'required|in:active,inactive',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data['parent_id']  = $seller->id;
        $data['shop_name']  = $seller->shop_name;
        $data['status']     = 'approved';
        $data['region']     = $seller->region;
        $data['password']   = Hash::make($data['password']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('seller_photos', 'public');
        }

        Seller::create($data);

        return redirect()->route('panel.sellers.show', $seller)
            ->with('success', "Hodim qo'shildi.");
    }

    // ── HODIM STATUS ───────────────────────────────────────────
    public function toggleStaff(Seller $seller)
    {
        abort_if($this->isMain($seller), 403);
        $new = $seller->staff_status === 'active' ? 'inactive' : 'active';
        $seller->update(['staff_status' => $new]);
        return back()->with('success',
            $new === 'active' ? 'Hodim faollashtirildi.' : "Hodim to'xtatildi."
        );
    }

    // ── STATUS ─────────────────────────────────────────────────
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

    public function export(Request $request)
    {
        return Excel::download(
            new SellersExport($request->all()),
            'sellers_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}