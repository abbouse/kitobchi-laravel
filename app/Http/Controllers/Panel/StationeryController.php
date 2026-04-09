<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Stationery;
use App\Models\StationeryCategory;
use App\Exports\StationeriesExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StationeryController extends Controller
{
    public function index(Request $request)
    {
        $q = Stationery::with(['category', 'seller']);

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('name', 'like', "%$s%")
                ->orWhere('id', $s)
                ->orWhere('seller_id', $s)
            );
        }

        $tab = $request->get('tab', 'pending');
        match ($tab) {
            'pending'  => $q->where('is_approved', 0),
            'approved' => $q->where('is_approved', 1),
            'rejected' => $q->where('is_approved', 2),
            'hidden'   => $q->where('is_hidden', 1),
            default    => null,
        };

        if ($request->filled('category_id')) {
            $q->where('category_id', $request->category_id);
        }

        $sort    = $request->get('sort', 'created_at');
        $dir     = $request->get('dir', 'desc');
        $allowed = ['id', 'name', 'price', 'stock', 'totalSales', 'created_at'];
        if (in_array($sort, $allowed)) {
            $q->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $stationeries = $q->paginate(25)->withQueryString();
        $categories   = StationeryCategory::where('is_active', 1)->orderBy('name_uz')->get();

        $counts = [
            'pending'  => Stationery::where('is_approved', 0)->count(),
            'approved' => Stationery::where('is_approved', 1)->count(),
            'rejected' => Stationery::where('is_approved', 2)->count(),
            'hidden'   => Stationery::where('is_hidden', 1)->count(),
        ];

        return view('panel.stationeries.index', compact('stationeries', 'categories', 'counts', 'tab'));
    }

    public function show(Stationery $stationery)
    {
        $stationery->load(['category', 'seller', 'variants', 'tags']);
        return view('panel.stationeries.show', compact('stationery'));
    }

    public function edit(Stationery $stationery)
    {
        $stationery->load(['category', 'seller', 'variants']);
        $categories = StationeryCategory::where('is_active', 1)->orderBy('name_uz')->get();
        return view('panel.stationeries.edit', compact('stationery', 'categories'));
    }

    public function update(Request $request, Stationery $stationery)
    {
        $request->validate([
            'is_approved'    => 'required|in:0,1,2',
            'is_hidden'      => 'required|boolean',
            'price'          => 'required|integer|min:0',
            'discount_price' => 'nullable|integer|min:0',
            'stock'          => 'required|integer|min:0',
        ]);

        $stationery->update($request->only([
            'is_approved', 'is_hidden', 'price', 'discount_price', 'stock',
        ]));

        return back()->with('success', 'Mahsulot yangilandi.');
    }

    public function moderate(Request $request, Stationery $stationery)
    {
        $request->validate(['action' => 'required|in:approve,reject']);
        $stationery->update(['is_approved' => $request->action === 'approve' ? 1 : 2]);
        $label = $request->action === 'approve' ? 'tasdiqlandi' : 'rad etildi';
        return back()->with('success', "Mahsulot $label.");
    }

    public function destroy(Stationery $stationery)
    {
        $stationery->delete();
        return redirect()->route('panel.stationeries.index')->with('success', "Mahsulot o'chirildi.");
    }

    public function export()
    {
        return Excel::download(new StationeriesExport, 'stationeries_' . now()->format('Y-m-d') . '.xlsx');
    }
}