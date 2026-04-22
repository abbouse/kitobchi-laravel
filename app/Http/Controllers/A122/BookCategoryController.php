<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\BookCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookCategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = BookCategories::withCount('books');

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
            'total'  => BookCategories::count(),
            'active' => BookCategories::where('is_active', 1)->count(),
        ];

        return view('a122.book-categories.index', compact('categories', 'stats'));
    }

    public function create()
    {
        return view('a122.book-categories.edit');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_uz' => 'required|string|max:80',
            'name_ru' => 'required|string|max:80',
            'name_en' => 'nullable|string|max:80',
            'name_ja' => 'nullable|string|max:80',
            'icon'    => 'nullable|string|max:10',
        ]);

        BookCategories::create([
            'name_uz'   => $request->name_uz,
            'name_ru'   => $request->name_ru,
            'name_en'   => $request->name_en ?? $request->name_uz,
            'name_ja'   => $request->name_ja ?? $request->name_uz,
            'icon'      => $request->icon,
            'slug'      => Str::slug($request->name_uz),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.book-categories.index')->with('success', "Kategoriya qo'shildi.");
    }

    public function edit(BookCategories $bookCategory)
    {
        return view('a122.book-categories.edit', compact('bookCategory'));
    }

    public function update(Request $request, BookCategories $bookCategory)
    {
        $request->validate([
            'name_uz' => 'required|string|max:80',
            'name_ru' => 'required|string|max:80',
            'name_en' => 'nullable|string|max:80',
            'name_ja' => 'nullable|string|max:80',
            'icon'    => 'nullable|string|max:10',
        ]);

        $bookCategory->update([
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

    public function toggle(BookCategories $bookCategory)
    {
        $bookCategory->update(['is_active' => !$bookCategory->is_active]);
        return back()->with('success', $bookCategory->is_active ? 'Faollashtirildi.' : "O'chirildi.");
    }

    public function destroy(BookCategories $bookCategory)
    {
        if ($bookCategory->books()->count() > 0) {
            return back()->with('error', "Bu kategoriyada kitoblar mavjud — o'chirib bo'lmaydi.");
        }
        $bookCategory->delete();
        return redirect()->route('admin.book-categories.index')->with('success', "Kategoriya o'chirildi.");
    }
}
