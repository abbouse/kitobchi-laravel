<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\StationeryCategory;
use App\Models\StationeryTag;
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
        $tags       = StationeryTag::orderBy('name_uz')->get();

        $stats = [
            'total'  => StationeryCategory::count(),
            'active' => StationeryCategory::where('is_active', 1)->count(),
        ];

        return view('panel.stationery-categories.index', compact('categories', 'tags', 'stats'));
    }

    public function create()
    {
        $tags = StationeryTag::orderBy('name_uz')->get();
        return view('panel.stationery-categories.edit', compact('tags'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_uz' => 'required|string|max:255',
            'name_ru' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'name_ja' => 'required|string|max:255',
            'icon'    => 'nullable|string|max:1',
            'slug'    => 'nullable|string|max:255|unique:stationery_categories,slug',
        ]);

        $cat = StationeryCategory::create([
            'name_uz'   => $request->name_uz,
            'name_ru'   => $request->name_ru,
            'name_en'   => $request->name_en,
            'name_ja'   => $request->name_ja,
            'icon'      => $request->icon,
            'slug'      => $request->slug ?: Str::slug($request->name_uz),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->filled('tags')) {
            $cat->tags()->sync($request->tags);
        }

        return redirect()->route('panel.stationery-categories.index')->with('success', "Kategoriya qo'shildi.");
    }

    public function edit(StationeryCategory $stationeryCategory)
    {
        $stationeryCategory->load('tags');
        $tags = StationeryTag::orderBy('name_uz')->get();
        return view('panel.stationery-categories.edit', compact('stationeryCategory', 'tags'));
    }

    public function update(Request $request, StationeryCategory $stationeryCategory)
    {
        $request->validate([
            'name_uz' => 'required|string|max:255',
            'name_ru' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'name_ja' => 'required|string|max:255',
            'icon'    => 'nullable|string|max:1',
        ]);

        $stationeryCategory->update([
            'name_uz'   => $request->name_uz,
            'name_ru'   => $request->name_ru,
            'name_en'   => $request->name_en,
            'name_ja'   => $request->name_ja,
            'icon'      => $request->icon,
            'slug'      => Str::slug($request->name_uz),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->has('tags')) {
            $stationeryCategory->tags()->sync($request->tags ?? []);
        }

        return back()->with('success', 'Kategoriya yangilandi.');
    }

    public function destroy(StationeryCategory $stationeryCategory)
    {
        if ($stationeryCategory->stationeries()->count() > 0) {
            return back()->with('error', "Bu kategoriyada mahsulotlar mavjud — o'chirib bo'lmaydi.");
        }
        $stationeryCategory->delete();
        return redirect()->route('panel.stationery-categories.index')->with('success', "Kategoriya o'chirildi.");
    }

    public function toggle(StationeryCategory $stationeryCategory)
    {
        $stationeryCategory->update(['is_active' => ! $stationeryCategory->is_active]);
        return back()->with('success', $stationeryCategory->is_active ? 'Faollashtirildi.' : "O'chirildi.");
    }
}