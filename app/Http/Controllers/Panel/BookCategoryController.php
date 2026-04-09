<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\BookCategories;
use App\Models\BookTag;
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
        $tags       = BookTag::orderBy('tag_name_uz')->get();

        $stats = [
            'total'  => BookCategories::count(),
            'active' => BookCategories::where('is_active', 1)->count(),
        ];

        return view('panel.book-categories.index', compact('categories', 'tags', 'stats'));
    }

    public function create()
    {
        $tags = BookTag::orderBy('tag_name_uz')->get();
        return view('panel.book-categories.edit', compact('tags'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_uz' => 'required|string|max:80',
            'name_ru' => 'required|string|max:80',
            'name_en' => 'required|string|max:80',
            'name_ja' => 'required|string|max:80',
            'icon'    => 'nullable|string|max:1',
        ]);

        $cat = BookCategories::create([
            'name_uz'   => $request->name_uz,
            'name_ru'   => $request->name_ru,
            'name_en'   => $request->name_en,
            'name_ja'   => $request->name_ja,
            'icon'      => $request->icon,
            'slug'      => Str::slug($request->name_uz),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->filled('tags')) {
            $cat->tags()->sync($request->tags);
        }

        return redirect()->route('panel.book-categories.index')->with('success', "Kategoriya qo'shildi.");
    }

    public function edit(BookCategory $bookCategory)
    {
        $bookCategory->load('tags');
        $tags = BookTag::orderBy('tag_name_uz')->get();
        return view('panel.book-categories.edit', compact('bookCategory', 'tags'));
    }

    public function update(Request $request, BookCategory $bookCategory)
    {
        $request->validate([
            'name_uz' => 'required|string|max:80',
            'name_ru' => 'required|string|max:80',
            'name_en' => 'required|string|max:80',
            'name_ja' => 'required|string|max:80',
            'icon'    => 'nullable|string|max:1',
        ]);

        $bookCategory->update([
            'name_uz'   => $request->name_uz,
            'name_ru'   => $request->name_ru,
            'name_en'   => $request->name_en,
            'name_ja'   => $request->name_ja,
            'icon'      => $request->icon,
            'slug'      => Str::slug($request->name_uz),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->has('tags')) {
            $bookCategory->tags()->sync($request->tags ?? []);
        }

        return back()->with('success', 'Kategoriya yangilandi.');
    }

    public function destroy(BookCategory $bookCategory)
    {
        if ($bookCategory->books()->count() > 0) {
            return back()->with('error', "Bu kategoriyada kitoblar mavjud — o'chirib bo'lmaydi.");
        }
        $bookCategory->delete();
        return redirect()->route('panel.book-categories.index')->with('success', "Kategoriya o'chirildi.");
    }

    public function toggle(BookCategory $bookCategory)
    {
        $bookCategory->update(['is_active' => ! $bookCategory->is_active]);
        return back()->with('success', $bookCategory->is_active ? 'Faollashtirildi.' : "O'chirildi.");
    }
}