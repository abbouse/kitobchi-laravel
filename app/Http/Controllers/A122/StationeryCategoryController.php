<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\StationeryCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StationeryCategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = StationeryCategory::withCount('stationeries');

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('name_uz', 'like', "%$s%")
                ->orWhere('name_ru', 'like', "%$s%")
            );
        }

        if ($request->filled('status')) {
            $q->where('is_active', $request->status);
        }

        $categories = $q->orderBy('name_uz')->paginate(20)->withQueryString();

        $stats = [
            'total'  => StationeryCategory::count(),
            'active' => StationeryCategory::where('is_active', 1)->count(),
        ];

        return view('a122.stationery-categories.index', compact('categories', 'stats'));
    }

    public function create()
    {
        return view('a122.stationery-categories.edit');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_uz' => 'required|string|max:255',
            'name_ru' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_ja' => 'nullable|string|max:255',
            'icon'    => 'nullable|string|max:10',
        ]);

        StationeryCategory::create([
            'name_uz'   => $request->name_uz,
            'name_ru'   => $request->name_ru,
            'name_en'   => $request->name_en ?? $request->name_uz,
            'name_ja'   => $request->name_ja ?? $request->name_uz,
            'icon'      => $request->icon,
            'slug'      => Str::slug($request->name_uz),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.stationery-categories.index')->with('success', "Kategoriya qo'shildi.");
    }

    public function edit(StationeryCategory $stationeryCategory)
    {
        return view('a122.stationery-categories.edit', compact('stationeryCategory'));
    }

    public function update(Request $request, StationeryCategory $stationeryCategory)
    {
        $request->validate([
            'name_uz' => 'required|string|max:255',
            'name_ru' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_ja' => 'nullable|string|max:255',
            'icon'    => 'nullable|string|max:10',
        ]);

        $stationeryCategory->update([
            'name_uz'   => $request->name_uz,
            'name_ru'   => $request->name_ru,
            'name_en'   => $request->name_en ?? $request->name_uz,
            'name_ja'   => $request->name_ja ?? $request->name_uz,
            'icon'      => $request->icon,
            'slug'      => Str::slug($request->name_uz),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Kategoriya yangilandi.');
    }

    public function toggle(StationeryCategory $stationeryCategory)
    {
        $stationeryCategory->update(['is_active' => !$stationeryCategory->is_active]);
        return back()->with('success', $stationeryCategory->is_active ? 'Faollashtirildi.' : "O'chirildi.");
    }

    public function destroy(StationeryCategory $stationeryCategory)
    {
        if ($stationeryCategory->stationeries()->count() > 0) {
            return back()->with('error', "Bu kategoriyada mahsulotlar mavjud — o'chirib bo'lmaydi.");
        }
        $stationeryCategory->delete();
        return redirect()->route('admin.stationery-categories.index')->with('success', "Kategoriya o'chirildi.");
    }
}
