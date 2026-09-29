<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use App\Models\BookCategories;
use App\Models\CuratedCollection;
use App\Models\HomeSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Boshqaruv → Marketing → Bosh sahifa: bo'limlar tartibi, yoqish/o'chirish,
 * sarlavhalar va qo'shimcha (janr / to'plam) bo'limlari.
 */
class HomeSectionsController extends Controller
{
    public function index(): Response
    {
        $sections = Schema::hasTable('home_sections')
            ? HomeSection::query()->orderBy('position')->orderBy('id')->get()
            : collect();

        return Inertia::render('HomeSections', [
            'sections' => $sections->map(fn (HomeSection $s) => [
                'id' => $s->id,
                'key' => $s->key,
                'type' => $s->type,
                'typeLabel' => HomeSection::TYPES[$s->type] ?? $s->type,
                'titleUz' => $s->title_uz,
                'titleRu' => $s->title_ru,
                'titleEn' => $s->title_en,
                'titleJa' => $s->title_ja,
                'isActive' => (bool) $s->is_active,
                'position' => (int) $s->position,
                'itemLimit' => (int) $s->item_limit,
                'settings' => (object) ($s->settings ?? []),
                'isCustom' => in_array($s->type, HomeSection::CUSTOM_TYPES, true),
                'updateUrl' => route('boshqaruv.home-sections.update', $s),
                'deleteUrl' => route('boshqaruv.home-sections.destroy', $s),
            ])->values(),
            'categories' => BookCategories::query()->where('is_active', 1)->orderBy('name_uz')
                ->get(['id', 'name_uz'])->map(fn ($c) => ['id' => $c->id, 'name' => $c->name_uz])->values(),
            'collections' => Schema::hasTable('curated_collections')
                ? CuratedCollection::query()->orderBy('sort_order')->get()
                    ->map(fn ($c) => ['id' => $c->id, 'name' => $c->localized('title', 'uz') ?: ('#'.$c->id)])->values()
                : [],
            'storeUrl' => route('boshqaruv.home-sections.store'),
            'reorderUrl' => route('boshqaruv.home-sections.reorder'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(HomeSection::CUSTOM_TYPES)],
            'title_uz' => ['required', 'string', 'max:120'],
            'title_ru' => ['nullable', 'string', 'max:120'],
            'title_en' => ['nullable', 'string', 'max:120'],
            'title_ja' => ['nullable', 'string', 'max:120'],
            'item_limit' => ['nullable', 'integer', 'min:4', 'max:30'],
            'category_id' => ['required_if:type,category', 'nullable', 'integer', 'exists:book_categories,id'],
            'collection_id' => ['required_if:type,collection', 'nullable', 'integer'],
        ]);

        $settings = $data['type'] === 'category'
            ? ['category_id' => (int) $data['category_id']]
            : ['collection_id' => (int) $data['collection_id']];

        // Do'konlardan oldingi oxirgi joy
        $shopsPos = (int) (HomeSection::query()->where('type', 'shops')->value('position') ?? 1000);
        $maxPos = (int) HomeSection::query()->where('type', '!=', 'shops')->max('position');

        HomeSection::query()->create([
            'key' => $data['type'].'_'.substr(md5(uniqid('', true)), 0, 8),
            'type' => $data['type'],
            'title_uz' => $data['title_uz'],
            'title_ru' => $data['title_ru'] ?? null,
            'title_en' => $data['title_en'] ?? null,
            'title_ja' => $data['title_ja'] ?? null,
            'is_active' => true,
            'position' => min($shopsPos - 1, $maxPos + 10),
            'item_limit' => (int) ($data['item_limit'] ?? 12),
            'settings' => $settings,
        ]);

        return back()->with('success', "Bo'lim qo'shildi.");
    }

    public function update(Request $request, HomeSection $section): RedirectResponse
    {
        $data = $request->validate([
            'title_uz' => ['nullable', 'string', 'max:120'],
            'title_ru' => ['nullable', 'string', 'max:120'],
            'title_en' => ['nullable', 'string', 'max:120'],
            'title_ja' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'item_limit' => ['nullable', 'integer', 'min:4', 'max:30'],
            'category_id' => ['nullable', 'integer', 'exists:book_categories,id'],
            'collection_id' => ['nullable', 'integer'],
        ]);

        $fill = collect($data)->only(['title_uz', 'title_ru', 'title_en', 'title_ja', 'is_active', 'item_limit'])
            ->filter(fn ($v) => $v !== null)->all();
        if ($section->type === 'category' && ! empty($data['category_id'])) {
            $fill['settings'] = ['category_id' => (int) $data['category_id']];
        }
        if ($section->type === 'collection' && ! empty($data['collection_id'])) {
            $fill['settings'] = ['collection_id' => (int) $data['collection_id']];
        }
        $section->update($fill);
        $this->flushCaches();

        return back()->with('success', "Bo'lim saqlandi.");
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($data) {
            foreach (array_values($data['ids']) as $i => $id) {
                HomeSection::query()->whereKey($id)->where('type', '!=', 'shops')->update(['position' => ($i + 1) * 10]);
            }
        });
        Cache::forget(HomeSection::CACHE_KEY);

        return back()->with('success', 'Tartib saqlandi.');
    }

    public function destroy(HomeSection $section): RedirectResponse
    {
        if (! in_array($section->type, HomeSection::CUSTOM_TYPES, true)) {
            return back()->with('error', "Asosiy bo'limni o'chirib bo'lmaydi — faqat o'chirib qo'yish mumkin.");
        }
        $section->delete();

        return back()->with('success', "Bo'lim o'chirildi.");
    }

    private function flushCaches(): void
    {
        Cache::forget(HomeSection::CACHE_KEY);
        foreach (['uz', 'ru', 'en'] as $locale) {
            foreach ([8, 10, 12, 15, 16, 20, 24, 30] as $limit) {
                Cache::forget("home_genres:{$locale}:{$limit}");
                Cache::forget("home_collections:{$locale}:{$limit}");
            }
        }
    }
}
