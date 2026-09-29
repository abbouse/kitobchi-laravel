<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookCategories;
use App\Models\CuratedCollection;
use App\Models\HomeSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Bosh sahifa: boshqaruvda sozlangan bo'limlar tartibi bilan.
 *
 * GET /home/layout          — barcha faol bo'limlar (do'konlardan tashqari)
 * GET /home/section/{key}   — bo'limning "Hammasi" sahifasi (sahifalab)
 * GET /home/shops?page=     — pastdagi do'konlar (scroll bilan yuklanadi)
 */
class HomeLayoutController extends Controller
{
    /** Foydalanuvchiga bog'liq bo'limlar keshlanmaydi. */
    private const PERSONAL_TYPES = ['for_you', 'recently_viewed'];

    public function layout(Request $request): JsonResponse
    {
        $locale = $this->locale($request);
        $user = Auth::guard('user')->user();
        $products = app(ProductsController::class);

        $sections = [];
        foreach (HomeSection::activeOrdered() as $section) {
            try {
                $payload = $this->sectionPayload($request, $section, $locale, $user !== null, $products);
                if ($payload !== null) {
                    $sections[] = $payload;
                }
            } catch (\Throwable $e) {
                Log::warning('Home section failed', ['key' => $section->key, 'error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => ['sections' => $sections],
            'meta' => ['generated_at' => now()->toIso8601String()],
        ]);
    }

    public function section(Request $request, string $key): JsonResponse
    {
        $section = HomeSection::query()->where('key', $key)->where('is_active', true)->first();
        if (! $section) {
            return response()->json(['status' => 'error', 'message' => "Bo'lim topilmadi"], 404);
        }
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $items = app(ProductsController::class)->homeSectionItems($request, $section, $page, $perPage);

        return response()->json([
            'status' => 'success',
            'data' => [
                'key' => $section->key,
                'title' => $section->title($this->locale($request)),
                'items' => $items,
                'page' => $page,
                'has_more' => count($items) >= $perPage,
            ],
        ]);
    }

    /** Janr chipi bosilganda: shu janr kitoblari (sahifalab). */
    public function category(Request $request, int $id): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $section = new HomeSection([
            'key' => 'category_'.$id,
            'type' => 'category',
            'item_limit' => $perPage,
            'settings' => ['category_id' => $id],
        ]);
        $items = app(ProductsController::class)->homeSectionItems($request, $section, $page, $perPage);
        $category = BookCategories::query()->find($id);
        $locale = $this->locale($request);

        return response()->json([
            'status' => 'success',
            'data' => [
                'key' => $section->key,
                'title' => $category ? (string) ($category->{'name_'.$locale} ?: $category->name_uz) : '',
                'items' => $items,
                'page' => $page,
                'has_more' => count($items) >= $perPage,
            ],
        ]);
    }

    public function shops(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $result = app(ProductsController::class)->homeShopsPage($request, $page, 4);

        return response()->json(['status' => 'success'] + $result);
    }

    private function sectionPayload(Request $request, HomeSection $section, string $locale, bool $loggedIn, ProductsController $products): ?array
    {
        $base = [
            'key' => $section->key,
            'type' => $section->type,
            'title' => $section->title($locale),
        ];

        switch ($section->type) {
            case 'center_banners':
            case 'shops':
                // Ilova o'zi chizadi (bannerlar /home dan, do'konlar /home/shops dan)
                return $base;

            case 'genres':
                $genres = $this->genres($locale, (int) $section->item_limit ?: 12);

                return $genres === [] ? null : $base + ['genres' => $genres];

            case 'collections':
                $collections = $this->collections($locale, (int) $section->item_limit ?: 12);

                return $collections === [] ? null : $base + ['collections' => $collections];
        }

        $personal = in_array($section->type, self::PERSONAL_TYPES, true);
        if ($section->type === 'recently_viewed' && ! $loggedIn) {
            return null;
        }

        $items = (! $personal && ! $loggedIn)
            ? Cache::remember("home_section_items:{$section->key}:{$locale}:{$section->updated_at?->timestamp}", 60,
                fn () => $products->homeSectionItems($request, $section))
            : $products->homeSectionItems($request, $section);

        if ($items === []) {
            return null;
        }

        return $base + [
            'items' => $items,
            'has_more' => count($items) >= (int) ($section->item_limit ?: 12),
        ];
    }

    /** Kitobi bor faol janrlar, kitoblar soni bo'yicha. */
    private function genres(string $locale, int $limit): array
    {
        return Cache::remember("home_genres:{$locale}:{$limit}", 600, function () use ($locale, $limit) {
            $counts = DB::table('books')
                ->where('status', true)->where('is_hidden', 0)->where('is_approved', 1)
                ->when(Schema::hasColumn('books', 'catalog_featured'), fn ($q) => $q->where('catalog_featured', true))
                ->whereNotNull('category_id')
                ->groupBy('category_id')
                ->selectRaw('category_id, COUNT(*) as c')
                ->pluck('c', 'category_id');

            return BookCategories::query()
                ->where('is_active', 1)
                ->whereIn('id', $counts->keys()->all())
                ->get(['id', 'name_uz', 'name_ru', 'name_en', 'icon'])
                ->sortByDesc(fn ($c) => (int) ($counts[$c->id] ?? 0))
                ->take($limit)
                ->map(fn ($c) => [
                    'id' => (int) $c->id,
                    'name' => (string) ($c->{'name_'.$locale} ?: $c->name_uz),
                    'icon' => $c->icon,
                    'count' => (int) ($counts[$c->id] ?? 0),
                ])
                ->values()
                ->all();
        });
    }

    /** Faol to'plamlar kartochkalari (ichidagi kitob muqovalari bilan). */
    private function collections(string $locale, int $limit): array
    {
        if (! Schema::hasTable('curated_collections')) {
            return [];
        }

        return Cache::remember("home_collections:{$locale}:{$limit}", 300, function () use ($locale, $limit) {
            return CuratedCollection::query()
                ->where('is_active', true)
                ->with(['items.book'])
                ->orderBy('sort_order')
                ->latest('id')
                ->take($limit)
                ->get()
                ->map(function (CuratedCollection $c) use ($locale) {
                    $covers = $c->items
                        ->map(fn ($item) => \App\Support\ProductImageUrls::build($item->book?->images)['thumb'][0] ?? null)
                        ->filter()
                        ->take(3)
                        ->values()
                        ->all();

                    return [
                        'id' => (int) $c->id,
                        'title' => $c->localized('title', $locale),
                        'subtitle' => $c->localized('subtitle', $locale),
                        'hero_image' => $c->hero_image,
                        'gradient_from' => $c->gradient_from,
                        'gradient_to' => $c->gradient_to,
                        'item_count' => $c->items->count(),
                        'covers' => $covers,
                    ];
                })
                ->filter(fn ($c) => $c['item_count'] > 0)
                ->values()
                ->all();
        });
    }

    private function locale(Request $request): string
    {
        $raw = strtolower((string) ($request->query('lang') ?: $request->header('Accept-Language', 'uz')));

        return str_starts_with($raw, 'ru') ? 'ru' : (str_starts_with($raw, 'en') ? 'en' : 'uz');
    }
}
