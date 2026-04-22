<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Couriers;
use App\Models\CourierOrder;
use App\Models\CourierTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CourierController extends Controller
{
    public function index(Request $request)
    {
        $query = Couriers::query();

        $tab = $request->input('tab', 'all');
        match ($tab) {
            'approved' => $query->where('status', 'approved'),
            'pending'  => $query->where('status', 'pending'),
            'rejected' => $query->where('status', 'rejected'),
            default    => null,
        };

        if ($s = $request->input('search')) {
            $query->where(fn($q) => $q
                ->where('first_name',     'like', "%$s%")
                ->orWhere('last_name',    'like', "%$s%")
                ->orWhere('phone_number', 'like', "%$s%")
                ->orWhere('region',       'like', "%$s%")
                ->orWhere('id', $s)
            );
        }

        $couriers = $query->latest()->paginate(20)->withQueryString();

        $counts = [
            'all'      => Couriers::count(),
            'approved' => Couriers::where('status', 'approved')->count(),
            'pending'  => Couriers::where('status', 'pending')->count(),
            'rejected' => Couriers::where('status', 'rejected')->count(),
        ];

        return view('a122.couriers.index', compact('couriers', 'counts', 'tab'));
    }

    public function create()
    {
        return view('a122.couriers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'   => 'required|string|max:25',
            'last_name'    => 'required|string|max:25',
            'phone_number' => 'required|string|unique:couriers,phone_number',
            'region'       => 'required|string|max:50',
            'password'     => 'required|string|min:6',
            'status'       => 'nullable|in:approved,pending,rejected',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['status']   = $data['status'] ?? 'pending';

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('courier_photos', 'public');
        }

        $courier = Couriers::create($data);
        return redirect()->route('admin.couriers.show', $courier)->with('success', "Kuryer yaratildi.");
    }

    public function show(Couriers $courier)
    {
        $orderCount  = CourierOrder::where('courier_id', $courier->id)->count();
        $totalEarned = CourierTransaction::where('courier_id', $courier->id)->where('status', 'approved')->sum('netAmount');
        $recentOrders = CourierOrder::with('user')->where('courier_id', $courier->id)->latest()->take(8)->get();

        return view('a122.couriers.show', compact('courier', 'orderCount', 'totalEarned', 'recentOrders'));
    }

    public function edit(Couriers $courier)
    {
        return view('a122.couriers.edit', compact('courier'));
    }

    public function update(Request $request, Couriers $courier)
    {
        $data = $request->validate([
            'first_name'   => 'required|string|max:25',
            'last_name'    => 'required|string|max:25',
            'phone_number' => ['required', 'string', Rule::unique('couriers', 'phone_number')->ignore($courier->id)],
            'region'       => 'required|string|max:50',
            'status'       => 'required|in:approved,pending,rejected',
            'balance'      => 'nullable|numeric|min:0',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        if ($request->hasFile('photo')) {
            if ($courier->photo) Storage::disk('public')->delete($courier->photo);
            $data['photo'] = $request->file('photo')->store('courier_photos', 'public');
        }

        $courier->update($data);
        return redirect()->route('admin.couriers.show', $courier)->with('success', "Kuryer yangilandi.");
    }

    public function approve(Couriers $courier)
    {
        $courier->update(['status' => 'approved']);
        return back()->with('success', 'Kuryer tasdiqlandi.');
    }

    public function reject(Couriers $courier)
    {
        $courier->update(['status' => 'rejected']);
        return back()->with('success', 'Kuryer rad etildi.');
    }

    public function destroy(Couriers $courier)
    {
        if ($courier->photo) Storage::disk('public')->delete($courier->photo);
        $courier->delete();
        return redirect()->route('admin.couriers.index')->with('success', "Kuryer o'chirildi.");
    }
}
