<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Books;
use App\Models\BookCategories;
use App\Models\ProductViewLog;
use App\Models\Stationery;
use App\Models\StationeryCategory;
use App\Models\User;
use App\Models\UserInterestProfile;
use App\Traits\HasProductVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ProductPersonalizationService
{
    use HasProductVisibility;

    public function recommendationKeys(Request $request, int $limit = 12, string $type = 'all'): Collection
    {
        $limit = max(1, min($limit, 40));
        $context = $this->viewContext($request);

        if ($context->isEmpty()) {
            return collect();
        }

        $signals = $this->signalsFromViews($context);

        if ($signals['viewed_book_ids'] === [] && $signals['viewed_stationery_ids'] === []) {
            return collect();
        }

        $items = collect();

        if (in_array($type, ['book', 'all'], true)) {
            $items = $items->merge($this->bookCandidates($signals, $limit));
        }

        if (in_array($type, ['stationery', 'all'], true)) {
            $items = $items->merge($this->stationeryCandidates($signals, $limit));
        }

        return $items
            ->sortByDesc('score')
            ->unique(fn (array $item) => "{$item['type']}:{$item['id']}")
            ->take($limit)
            ->values();
    }

    public function refreshUserInterestProfile(User $user, int $days = 90, bool $persist = true): array
    {
        $views = ProductViewLog::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(max(7, $days)))
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->limit(300)
            ->get(['product_type', 'product_id', 'created_at', 'updated_at']);

        if ($views->isEmpty()) {
            $payload = [
                'profile' => [
                    'summary' => 'Foydalanuvchi qiziqishi uchun hali yetarli mahsulot ko‘rish tarixi yo‘q.',
                    'dominant_product_type' => null,
                    'top_book_categories' => [],
                    'top_stationery_categories' => [],
                    'top_authors' => [],
                ],
                'signals' => [],
                'views_count' => 0,
                'confidence_score' => 0,
                'last_viewed_at' => null,
                'generated_at' => now(),
            ];

            if ($persist && Schema::hasTable('user_interest_profiles')) {
                UserInterestProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    $payload + ['user_id' => $user->id],
                );
            }

            return $payload;
        }

        $signals = $this->signalsFromViews($views);
        $profile = $this->buildReadableProfile($signals, $views);
        $confidence = $this->confidenceScore($signals, $views->count());
        $lastViewedAt = $views
            ->map(fn ($view) => $view->updated_at ?: $view->created_at)
            ->filter()
            ->max();

        $payload = [
            'profile' => $profile,
            'signals' => $signals,
            'views_count' => $views->count(),
            'confidence_score' => $confidence,
            'last_viewed_at' => $lastViewedAt ? Carbon::parse($lastViewedAt) : null,
            'generated_at' => now(),
        ];

        if ($persist && Schema::hasTable('user_interest_profiles')) {
            UserInterestProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                $payload + ['user_id' => $user->id],
            );
        }

        return $payload;
    }

    public function refreshUsers(int $limit = 1000, int $days = 90, bool $persist = true): int
    {
        if (! Schema::hasTable('product_view_logs')) {
            return 0;
        }

        $userIds = ProductViewLog::query()
            ->whereNotNull('user_id')
            ->where('created_at', '>=', now()->subDays(max(7, $days)))
            ->selectRaw('user_id, MAX(COALESCE(updated_at, created_at)) as last_seen_at')
            ->groupBy('user_id')
            ->orderByDesc('last_seen_at')
            ->limit(max(1, $limit))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $count = 0;

        User::query()
            ->whereIn('id', $userIds)
            ->chunkById(200, function ($users) use (&$count, $days, $persist) {
                foreach ($users as $user) {
                    $this->refreshUserInterestProfile($user, $days, $persist);
                    $count++;
                }
            });

        return $count;
    }

    public function cleanupGuestViews(int $days = 45): int
    {
        if (! Schema::hasTable('product_view_logs')) {
            return 0;
        }

        return ProductViewLog::query()
            ->whereNull('user_id')
            ->where('created_at', '<', now()->subDays(max(7, $days)))
            ->delete();
    }

    private function viewContext(Request $request): Collection
    {
        $user = $this->requestUser($request);
        $sessionId = trim((string) $request->header('X-Session-Id', ''));
        $deviceId = trim((string) $request->header('X-Device-Id', ''));

        $query = ProductViewLog::query()
            ->where('created_at', '>=', now()->subDays(90))
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->limit(100);

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($sessionId !== '') {
            $query->where('session_id', $sessionId);
        } elseif ($deviceId !== '' && $deviceId !== 'unknown_device') {
            $query->where('device_id', $deviceId);
        } else {
            return collect();
        }

        return $query->get(['product_type', 'product_id', 'created_at']);
    }

    private function signalsFromViews(Collection $views): array
    {
        $bookIds = $views
            ->where('product_type', 'book')
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $stationeryIds = $views
            ->where('product_type', 'stationery')
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $books = $bookIds === []
            ? collect()
            : Books::query()
                ->whereIn('id', $bookIds)
                ->get(['id', 'category_id', 'author_id', 'author', 'seller_id']);

        $stationeries = $stationeryIds === []
            ? collect()
            : Stationery::query()
                ->whereIn('id', $stationeryIds)
                ->get(['id', 'category_id', 'seller_id']);

        return [
            'viewed_book_ids' => $bookIds,
            'viewed_stationery_ids' => $stationeryIds,
            'book_category_weights' => $this->weightedCounts($books->pluck('category_id')),
            'stationery_category_weights' => $this->weightedCounts($stationeries->pluck('category_id')),
            'author_id_weights' => $this->weightedCounts($books->pluck('author_id')),
            'author_name_weights' => $this->weightedCounts($books->pluck('author')),
            'seller_weights' => $this->weightedCounts($books->pluck('seller_id')->merge($stationeries->pluck('seller_id'))),
        ];
    }

    private function bookCandidates(array $signals, int $limit): Collection
    {
        $categoryIds = array_keys($signals['book_category_weights']);
        $authorIds = array_keys($signals['author_id_weights']);
        $authorNames = array_keys($signals['author_name_weights']);
        $sellerIds = array_keys($signals['seller_weights']);

        if ($categoryIds === [] && $authorIds === [] && $authorNames === [] && $sellerIds === []) {
            return collect();
        }

        return $this->visibleBooks(['category', 'seller', 'tags'])
            ->whereNotIn('id', $signals['viewed_book_ids'])
            ->where(function ($query) use ($categoryIds, $authorIds, $authorNames, $sellerIds) {
                if ($categoryIds !== []) {
                    $query->orWhereIn('category_id', $categoryIds);
                }
                if ($authorIds !== []) {
                    $query->orWhereIn('author_id', $authorIds);
                }
                foreach ($authorNames as $authorName) {
                    $query->orWhere('author', $authorName);
                }
                if ($sellerIds !== []) {
                    $query->orWhereIn('seller_id', $sellerIds);
                }
                $query->orWhere('recommended', true);
            })
            ->orderByDesc('totalSalesWeek')
            ->orderByDesc('totalSales')
            ->limit($limit * 3)
            ->get()
            ->map(fn (Books $book) => [
                'type' => 'book',
                'id' => (int) $book->id,
                'score' => $this->scoreBook($book, $signals),
            ]);
    }

    private function stationeryCandidates(array $signals, int $limit): Collection
    {
        $categoryIds = array_keys($signals['stationery_category_weights']);
        $sellerIds = array_keys($signals['seller_weights']);

        if ($categoryIds === [] && $sellerIds === []) {
            return collect();
        }

        return $this->visibleStationeries(['category', 'seller', 'tags', 'variants'])
            ->whereNotIn('id', $signals['viewed_stationery_ids'])
            ->where(function ($query) use ($categoryIds, $sellerIds) {
                if ($categoryIds !== []) {
                    $query->orWhereIn('category_id', $categoryIds);
                }
                if ($sellerIds !== []) {
                    $query->orWhereIn('seller_id', $sellerIds);
                }
                $query->orWhere('recommended', true);
            })
            ->orderByDesc('totalSalesWeek')
            ->orderByDesc('totalSales')
            ->limit($limit * 2)
            ->get()
            ->map(fn (Stationery $stationery) => [
                'type' => 'stationery',
                'id' => (int) $stationery->id,
                'score' => $this->scoreStationery($stationery, $signals),
            ]);
    }

    private function scoreBook(Books $book, array $signals): float
    {
        $score = 0;
        $score += 12 * (int) ($signals['book_category_weights'][(string) $book->category_id] ?? 0);
        $score += 14 * (int) ($signals['author_id_weights'][(string) $book->author_id] ?? 0);
        $score += 10 * (int) ($signals['author_name_weights'][(string) $book->author] ?? 0);
        $score += 3 * (int) ($signals['seller_weights'][(string) $book->seller_id] ?? 0);
        $score += (bool) ($book->recommended ?? false) ? 8 : 0;
        $score += min(8, ((int) ($book->totalSalesWeek ?? 0)) / 5);
        $score += min(4, ((int) ($book->totalSales ?? 0)) / 50);

        return $score;
    }

    private function scoreStationery(Stationery $stationery, array $signals): float
    {
        $score = 0;
        $score += 12 * (int) ($signals['stationery_category_weights'][(string) $stationery->category_id] ?? 0);
        $score += 3 * (int) ($signals['seller_weights'][(string) $stationery->seller_id] ?? 0);
        $score += (bool) ($stationery->recommended ?? false) ? 8 : 0;
        $score += min(8, ((int) ($stationery->totalSalesWeek ?? 0)) / 5);
        $score += min(4, ((int) ($stationery->totalSales ?? 0)) / 50);

        return $score;
    }

    private function buildReadableProfile(array $signals, Collection $views): array
    {
        $bookViews = $views->where('product_type', 'book')->count();
        $stationeryViews = $views->where('product_type', 'stationery')->count();
        $dominantType = $bookViews === $stationeryViews
            ? 'mixed'
            : ($bookViews > $stationeryViews ? 'book' : 'stationery');

        $bookCategoryLabels = $this->bookCategoryLabels(array_keys($signals['book_category_weights']));
        $stationeryCategoryLabels = $this->stationeryCategoryLabels(array_keys($signals['stationery_category_weights']));
        $authorLabels = $this->authorLabels($signals);

        $topBookCategories = $this->labelWeights($signals['book_category_weights'], $bookCategoryLabels, 6);
        $topStationeryCategories = $this->labelWeights($signals['stationery_category_weights'], $stationeryCategoryLabels, 6);
        $topAuthors = $this->labelWeights($signals['author_id_weights'], $authorLabels, 6)
            ->merge($this->labelWeights($signals['author_name_weights'], [], 6))
            ->unique('label')
            ->take(6)
            ->values()
            ->all();

        return [
            'summary' => $this->profileSummary($dominantType, $topBookCategories, $topStationeryCategories, $topAuthors),
            'dominant_product_type' => $dominantType,
            'book_views' => $bookViews,
            'stationery_views' => $stationeryViews,
            'top_book_categories' => $topBookCategories,
            'top_stationery_categories' => $topStationeryCategories,
            'top_authors' => $topAuthors,
            'viewed_products' => [
                'books' => count($signals['viewed_book_ids']),
                'stationery' => count($signals['viewed_stationery_ids']),
            ],
        ];
    }

    private function profileSummary(string $dominantType, array $bookCategories, array $stationeryCategories, array $authors): string
    {
        $parts = [];

        $parts[] = match ($dominantType) {
            'book' => 'Foydalanuvchi asosan kitoblarga qiziqmoqda.',
            'stationery' => 'Foydalanuvchi asosan kanselyariya mahsulotlariga qiziqmoqda.',
            default => 'Foydalanuvchi kitob va kanselyariyani aralash ko‘rmoqda.',
        };

        if ($bookCategories !== []) {
            $parts[] = 'Kitob kategoriyalari: '.collect($bookCategories)->pluck('label')->take(3)->implode(', ').'.';
        }

        if ($authors !== []) {
            $parts[] = 'Mualliflar: '.collect($authors)->pluck('label')->take(3)->implode(', ').'.';
        }

        if ($stationeryCategories !== []) {
            $parts[] = 'Kanselyariya yo‘nalishlari: '.collect($stationeryCategories)->pluck('label')->take(3)->implode(', ').'.';
        }

        return implode(' ', $parts);
    }

    private function confidenceScore(array $signals, int $viewsCount): float
    {
        $signalCount = count($signals['book_category_weights'])
            + count($signals['stationery_category_weights'])
            + count($signals['author_id_weights'])
            + count($signals['author_name_weights'])
            + count($signals['seller_weights']);

        return round(min(100, ($viewsCount * 4) + ($signalCount * 5)), 2);
    }

    private function labelWeights(array $weights, array $labels, int $limit): Collection
    {
        return collect($weights)
            ->map(fn ($weight, $id) => [
                'id' => is_numeric($id) ? (int) $id : (string) $id,
                'label' => $labels[(string) $id] ?? (string) $id,
                'weight' => (int) $weight,
            ])
            ->filter(fn (array $item) => trim((string) $item['label']) !== '')
            ->sortByDesc('weight')
            ->take($limit)
            ->values();
    }

    private function bookCategoryLabels(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return BookCategories::query()
            ->whereIn('id', $ids)
            ->pluck('name_uz', 'id')
            ->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name])
            ->all();
    }

    private function stationeryCategoryLabels(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return StationeryCategory::query()
            ->whereIn('id', $ids)
            ->pluck('name_uz', 'id')
            ->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name])
            ->all();
    }

    private function authorLabels(array $signals): array
    {
        $ids = array_keys($signals['author_id_weights']);

        if ($ids === []) {
            return [];
        }

        return Author::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name])
            ->all();
    }

    private function weightedCounts(Collection $values): array
    {
        return $values
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => is_string($value) ? trim($value) : (string) $value)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->all();
    }

    private function requestUser(Request $request): ?User
    {
        $user = $request->user('user') ?? auth('sanctum')->user();

        return $user instanceof User ? $user : null;
    }
}
