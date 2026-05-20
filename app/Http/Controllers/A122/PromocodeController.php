<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Promocode;
use App\Models\PromocodeHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromocodeController extends Controller
{
    public function index(Request $request)
    {
        $q = Promocode::withCount('histories');

        if ($s = $request->search) {
            $q->where(fn($x) => $x->where('code', 'like', "%$s%")->orWhere('id', $s));
        }

        $tab = $request->get('tab', 'all');
        match ($tab) {
            'active'  => $q->where('status', 1)->where('expires_at', '>', now()),
            'expired' => $q->where(fn($x) => $x->where('status', 0)->orWhere('expires_at', '<=', now())),
            default   => null,
        };

        $promocodes = $q->latest()->paginate(20)->withQueryString();

        $counts = [
            'all'     => Promocode::count(),
            'active'  => Promocode::where('status', 1)->where('expires_at', '>', now())->count(),
            'expired' => Promocode::where(fn($x) => $x->where('status', 0)->orWhere('expires_at', '<=', now()))->count(),
        ];

        return view('a122.promocodes.index', compact('promocodes', 'counts', 'tab'));
    }

    public function create()
    {
        return view('a122.promocodes.edit');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'             => 'required|string|max:255|unique:promocodes,code',
            'type'             => 'required|in:percent,fixed',
            'amount'           => 'required|integer|min:1',
            'max_discount_amount' => 'nullable|integer|min:0',
            'min_order_amount' => 'nullable|integer|min:0',
            'per_user_limit'   => 'nullable|integer|min:0',
            'usesLimit'        => 'nullable|integer|min:0',
            'expires_at'       => 'required|date|after:now',
            'status'           => 'required|boolean',
        ]);

        Promocode::create([
            'code'             => strtoupper($request->code),
            'type'             => $request->type,
            'amount'           => $request->amount,
            'max_discount_amount' => $request->type === 'percent'
                ? ($request->filled('max_discount_amount') ? (int) $request->max_discount_amount : null)
                : null,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'per_user_limit'   => $request->filled('per_user_limit') ? (int) $request->per_user_limit : 1,
            'usesLimit'        => $request->usesLimit ?? 0,
            'usedCount'        => 0,
            'status'           => $request->status,
            'expires_at'       => $request->expires_at,
        ]);

        return redirect()->route('admin.promocodes.index')->with('success', "Promokod qo'shildi.");
    }

    public function show(Promocode $promocode)
    {
        $histories = PromocodeHistory::with('user:id,name,lastname,phone_number')
            ->where('promocode_id', $promocode->id)
            ->latest()->paginate(20);

        return view('a122.promocodes.show', compact('promocode', 'histories'));
    }

    public function edit(Promocode $promocode)
    {
        return view('a122.promocodes.edit', compact('promocode'));
    }

    public function update(Request $request, Promocode $promocode)
    {
        $request->validate([
            'type'             => 'required|in:percent,fixed',
            'amount'           => 'required|integer|min:1',
            'max_discount_amount' => 'nullable|integer|min:0',
            'min_order_amount' => 'nullable|integer|min:0',
            'per_user_limit'   => 'nullable|integer|min:0',
            'usesLimit'        => 'nullable|integer|min:0',
            'expires_at'       => 'required|date',
            'status'           => 'required|boolean',
        ]);

        $promocode->update([
            'type' => $request->type,
            'amount' => $request->amount,
            'max_discount_amount' => $request->type === 'percent'
                ? ($request->filled('max_discount_amount') ? (int) $request->max_discount_amount : null)
                : null,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'per_user_limit' => $request->filled('per_user_limit') ? (int) $request->per_user_limit : 1,
            'usesLimit' => $request->usesLimit ?? 0,
            'expires_at' => $request->expires_at,
            'status' => $request->status,
        ]);
        return back()->with('success', 'Promokod yangilandi.');
    }

    public function destroy(Promocode $promocode)
    {
        $promocode->delete();
        return redirect()->route('admin.promocodes.index')->with('success', "Promokod o'chirildi.");
    }

    public function generate()
    {
        return response()->json(['code' => strtoupper(Str::random(8))]);
    }
}
