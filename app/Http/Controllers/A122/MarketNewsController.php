<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\MarketNews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MarketNewsController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketNews::with(['seller:id,shop_name', 'book:id,name'])->latest();

        $tab = $request->input('tab', 'all');
        match ($tab) {
            'active' => $query->where('status', true),
            'news' => $query->where('action', 'news'),
            default => null,
        };

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($inner) use ($search) {
                $inner->where('id', $search)
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('seller', fn ($sellerQuery) => $sellerQuery->where('shop_name', 'like', "%{$search}%"))
                    ->orWhereHas('book', fn ($bookQuery) => $bookQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('artikul', 'like', "%{$search}%"));
            });
        }

        $news = $query->paginate(20)->withQueryString();

        $counts = [
            'all'    => MarketNews::count(),
            'active' => MarketNews::where('status', true)->count(),
            'news'   => MarketNews::where('action', 'news')->count(),
        ];

        return view('a122.news.index', compact('news', 'counts', 'tab'));
    }

    public function create()
    {
        return view('a122.news.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateNews($request);

        if ($request->hasFile('imgUrl')) {
            $data['imgUrl'] = $request->file('imgUrl')->store('blog', 'public');
        }

        $news = MarketNews::create($data);
        return redirect()->route('admin.news.show', $news)->with('success', 'Yangilik yaratildi.');
    }

    public function show(MarketNews $news)
    {
        $news->load([
            'seller:id,shop_name',
            'book:id,name,author',
            'collection:id,slug,title_uz,title_ru,title_en,title_ja',
        ]);

        $target = match ($news->normalizedAction()) {
            MarketNews::ACTION_TO_SHOP => $news->seller,
            MarketNews::ACTION_TO_PRODUCT => $news->book,
            MarketNews::ACTION_TO_COLLECTION => $news->collection,
            default => null,
        };

        return view('a122.news.show', compact('news', 'target'));
    }

    public function edit(MarketNews $news)
    {
        return view('a122.news.edit', compact('news'));
    }

    public function update(Request $request, MarketNews $news)
    {
        $data = $this->validateNews($request, $news);

        if ($request->hasFile('imgUrl')) {
            if ($news->imgUrl) Storage::disk('public')->delete($news->imgUrl);
            $data['imgUrl'] = $request->file('imgUrl')->store('blog', 'public');
        }

        $news->update($data);
        return redirect()->route('admin.news.show', $news)->with('success', 'Yangilik yangilandi.');
    }

    public function toggle(MarketNews $news)
    {
        $news->update(['status' => !$news->status]);
        return back()->with('success', $news->status ? 'Faollashtirildi.' : "O'chirildi.");
    }

    public function destroy(MarketNews $news)
    {
        if ($news->imgUrl) Storage::disk('public')->delete($news->imgUrl);
        $news->delete();
        return redirect()->route('admin.news.index')->with('success', "Yangilik o'chirildi.");
    }

    private function validateNews(Request $request, ?MarketNews $news = null): array
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:255',
            'title_uz'    => 'nullable|string|max:255',
            'title_ru'    => 'nullable|string|max:255',
            'title_en'    => 'nullable|string|max:255',
            'title_ja'    => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_uz' => 'nullable|string',
            'description_ru' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_ja' => 'nullable|string',
            'align'       => 'required|in:top,center',
            'status'      => 'boolean',
            'action'      => 'required|in:'.implode(',', MarketNews::allowedActions()),
            'action_id'   => 'nullable|integer|min:1',
            'imgUrl'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $data = $this->normalizeLocalizedField($data, 'title', required: true);
        $data = $this->normalizeLocalizedField($data, 'description');

        if (in_array($data['action'], [MarketNews::ACTION_NEWS, MarketNews::ACTION_TO_BOTTOMSHEET], true)) {
            $data['action_id'] = null;
        }
        $data['status'] = $request->boolean('status');

        return $data;
    }

    private function normalizeLocalizedField(array $data, string $field, bool $required = false): array
    {
        $legacyValue = isset($data[$field]) ? trim((string) $data[$field]) : null;
        $uzValue = isset($data["{$field}_uz"]) ? trim((string) $data["{$field}_uz"]) : null;
        $resolvedUz = filled($uzValue) ? $uzValue : $legacyValue;

        if ($required && ! filled($resolvedUz)) {
            throw ValidationException::withMessages([
                "{$field}_uz" => $field === 'title'
                    ? 'Uzbekcha sarlavha majburiy.'
                    : 'Uzbekcha matn majburiy.',
            ]);
        }

        $data[$field] = filled($resolvedUz) ? $resolvedUz : null;
        $data["{$field}_uz"] = filled($resolvedUz) ? $resolvedUz : null;

        foreach (['ru', 'en', 'ja'] as $locale) {
            $key = "{$field}_{$locale}";
            $value = isset($data[$key]) ? trim((string) $data[$key]) : null;
            $data[$key] = filled($value) ? $value : null;
        }

        return $data;
    }
}
