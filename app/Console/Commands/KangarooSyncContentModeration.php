<?php

namespace App\Console\Commands;

use App\Models\Books;
use App\Models\Stationery;
use App\Services\Kangaroo\KangarooKitobchiModerationClient;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class KangarooSyncContentModeration extends Command
{
    protected $signature = 'kangaroo:sync-content-moderation {--listings=1}';

    protected $description = 'Kangaroo: faqat kitob va kanstovar listing moderatsiyasi';

    public function handle(KangarooKitobchiModerationClient $client): int
    {
        $autoApply = filter_var(env('KANGAROO_LISTING_AUTO_APPLY', false), FILTER_VALIDATE_BOOLEAN);

        try {
            if ((bool) $this->option('listings')) {
                $this->syncListings($client, $autoApply);
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            Log::error('kangaroo:sync-content-moderation', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function syncListings(KangarooKitobchiModerationClient $client, bool $autoApply): void
    {
        if (!$this->hasListingModerationColumns('books') || !$this->hasListingModerationColumns('stationery')) {
            $message = 'Kangaroo listing ustunlari hali migratsiya qilinmagan. `php artisan migrate`dan keyin qayta urinib ko‘ring.';
            $this->warn($message);
            Log::warning('kangaroo:sync-content-moderation-skipped', ['message' => $message]);

            return;
        }

        $books = Books::query()
            ->where('is_approved', 0)
            ->where('is_hidden', 0)
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('kangaroo_listing_checked_at')
                    ->orWhereColumn('updated_at', '>', 'kangaroo_listing_checked_at');
            })
            ->orderByDesc('updated_at')
            ->limit(45)
            ->get(['id', 'name', 'author', 'description', 'price', 'images']);

        $stationeries = Stationery::query()
            ->where('is_approved', 0)
            ->where('is_hidden', 0)
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('kangaroo_listing_checked_at')
                    ->orWhereColumn('updated_at', '>', 'kangaroo_listing_checked_at');
            })
            ->orderByDesc('updated_at')
            ->limit(45)
            ->get(['id', 'name', 'description', 'price', 'images']);

        if ($books->isEmpty() && $stationeries->isEmpty()) {
            $this->info('Listing: navbat bo‘sh.');

            return;
        }

        $payloadBooks = $books->map(fn ($b) => [
            'id' => $b->id,
            'name' => $b->name,
            'author' => $b->author,
            'description' => $b->description,
            'price' => (int) $b->price,
            'images' => $b->images,
        ])->values()->all();

        $payloadSt = $stationeries->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'description' => $s->description,
            'price' => (int) $s->price,
            'images' => $s->images,
        ])->values()->all();

        try {
            $resp = $client->moderateListings($payloadBooks, $payloadSt);
        } catch (\Throwable $e) {
            Log::warning('Kangaroo listing: API xato — mahsulotlar inson ko‘rib chiqishiga (human_review)', [
                'message' => $e->getMessage(),
            ]);
            $this->warn('Listing: Kangaroo javob bermadi — '.$e->getMessage().' (inson moderatsiyasiga yuborildi).');
            $this->markListingsHumanReviewOnFailure($books, $stationeries, $e->getMessage());

            return;
        }

        $returnedBookIds = $this->collectIdsFromListingRows($resp['books'] ?? []);
        $returnedStIds = $this->collectIdsFromListingRows($resp['stationeries'] ?? []);

        foreach ($resp['books'] ?? [] as $r) {
            $this->applyListingResult(Books::class, (int) ($r['id'] ?? 0), $r, $autoApply);
        }
        foreach ($resp['stationeries'] ?? [] as $r) {
            $this->applyListingResult(Stationery::class, (int) ($r['id'] ?? 0), $r, $autoApply);
        }

        foreach ($books as $b) {
            if (! in_array((int) $b->id, $returnedBookIds, true)) {
                $this->applyListingResult(Books::class, (int) $b->id, [
                    'decision' => 'human_review',
                    'score' => null,
                    'issues' => [['code' => 'missing_kangaroo_response', 'severity' => 'warn']],
                ], $autoApply);
            }
        }
        foreach ($stationeries as $s) {
            if (! in_array((int) $s->id, $returnedStIds, true)) {
                $this->applyListingResult(Stationery::class, (int) $s->id, [
                    'decision' => 'human_review',
                    'score' => null,
                    'issues' => [['code' => 'missing_kangaroo_response', 'severity' => 'warn']],
                ], $autoApply);
            }
        }

        $this->info('Listing: kitob '.$books->count().', kanstovar '.$stationeries->count().' jarayonlandi.');
    }

    private function hasListingModerationColumns(string $table): bool
    {
        foreach ([
            'kangaroo_listing_decision',
            'kangaroo_listing_score',
            'kangaroo_listing_checked_at',
            'kangaroo_listing_issues',
        ] as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<int>
     */
    private function collectIdsFromListingRows(array $rows): array
    {
        $ids = [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param  Collection<int, Books>  $books
     * @param  Collection<int, Stationery>  $stationeries
     */
    private function markListingsHumanReviewOnFailure(Collection $books, Collection $stationeries, string $message): void
    {
        $issue = [
            'code' => 'kangaroo_client_error',
            'severity' => 'warn',
            'detail' => mb_substr($message, 0, 240),
        ];

        foreach ($books as $b) {
            Books::query()->where('id', $b->id)->update([
                'kangaroo_listing_decision' => 'human_review',
                'kangaroo_listing_score' => null,
                'kangaroo_listing_issues' => [$issue],
                'kangaroo_listing_checked_at' => now(),
            ]);
        }
        foreach ($stationeries as $s) {
            Stationery::query()->where('id', $s->id)->update([
                'kangaroo_listing_decision' => 'human_review',
                'kangaroo_listing_score' => null,
                'kangaroo_listing_issues' => [$issue],
                'kangaroo_listing_checked_at' => now(),
            ]);
        }
    }

    /**
     * @param  class-string<Books|Stationery>  $modelClass
     * @param  array<string, mixed>  $r
     */
    private function applyListingResult(string $modelClass, int $id, array $r, bool $autoApply): void
    {
        if ($id <= 0) {
            return;
        }

        $rawDecision = (string) ($r['decision'] ?? '');
        $needsAdmin = ! empty($r['needs_admin_review']);

        $allowed = ['approve', 'reject', 'human_review'];
        if ($rawDecision === '' || ! in_array($rawDecision, $allowed, true)) {
            $decision = 'human_review';
            $invalidNote = [['code' => 'invalid_or_empty_decision', 'severity' => 'warn', 'detail' => $rawDecision]];
        } else {
            $decision = $rawDecision;
            $invalidNote = [];
        }

        if ($needsAdmin && $decision === 'approve') {
            $decision = 'human_review';
            $invalidNote[] = ['code' => 'needs_admin_review', 'severity' => 'warn'];
        }

        $issues = $r['issues'] ?? [];
        if (! is_array($issues)) {
            $issues = [];
        }
        $issues = array_merge($issues, $invalidNote);

        $score = array_key_exists('score', $r) && $r['score'] !== null && $r['score'] !== ''
            ? round(max(1, min(5, (float) $r['score'])), 1)
            : null;

        $update = [
            'kangaroo_listing_decision' => $decision,
            'kangaroo_listing_score' => $score,
            'kangaroo_listing_issues' => $issues,
            'kangaroo_listing_checked_at' => now(),
        ];

        if ($autoApply) {
            if ($decision === 'approve') {
                $update['is_approved'] = 1;
            } elseif ($decision === 'reject') {
                $update['is_approved'] = 0;
            }
        }

        $modelClass::query()->where('id', $id)->update($update);
    }
}
