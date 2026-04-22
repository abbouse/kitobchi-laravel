<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SellerController extends Controller
{
    private function mainScope($query)
    {
        return $query->where(fn($q) => $q->whereNull('parent_id')->orWhere('parent_id', 0));
    }

    public function index(Request $request)
    {
        $query = Seller::withCount(['books', 'orders']);
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

        $base = fn() => Seller::where(fn($q) => $q->whereNull('parent_id')->orWhere('parent_id', 0));
        $counts = [
            'pending'  => $base()->where('status', 'pending')->count(),
            'approved' => $base()->where('status', 'approved')->count(),
            'rejected' => $base()->where('status', 'rejected')->count(),
            'all'      => $base()->count(),
        ];

        return view('a122.sellers.index', compact('sellers', 'counts', 'tab'));
    }

    public function show(Seller $seller)
    {
        $seller->loadCount('books')->load(['books', 'location']);
        $orderCount   = SellerOrder::where('seller_id', $seller->id)->count();
        $totalRevenue = SellerTransaction::where('seller_id', $seller->id)->where('status', 'approved')->sum('amount');
        $recentOrders = SellerOrder::where('seller_id', $seller->id)->latest()->take(8)->get();
        $transactions = SellerTransaction::where('seller_id', $seller->id)->latest()->take(8)->get();

        return view('a122.sellers.show', compact('seller', 'orderCount', 'totalRevenue', 'recentOrders', 'transactions'));
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
            'status'       => 'required|in:pending,approved,rejected',
            'balance'      => 'nullable|numeric|min:0',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
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
}
