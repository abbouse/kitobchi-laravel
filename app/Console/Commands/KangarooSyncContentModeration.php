<?php

namespace App\Console\Commands;

use App\Models\BookClub;
use App\Models\BookClubComment;
use App\Models\Books;
use App\Models\Stationery;
use App\Services\Kangaroo\KangarooKitobchiModerationClient;
use App\Support\BookClubUgcSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class KangarooSyncContentModeration extends Command
{
    protected $signature = 'kangaroo:sync-content-moderation {--listings=1} {--ugc=1}';

    protected $description = 'Kangaroo: listing moderatsiyasi va book club UGC baholari (30 daqiqada scheduler)';

    public function handle(KangarooKitobchiModerationClient $client): int
    {
        $autoApply = filter_var(env('KANGAROO_LISTING_AUTO_APPLY', false), FILTER_VALIDATE_BOOLEAN);

        try {
            if ((bool) $this->option('listings')) {
                $this->syncListings($client, $autoApply);
            }
            if ((bool) $this->option('ugc')) {
                $this->syncUgc($client);
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
            ? (int) $r['score']
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

    private function syncUgc(KangarooKitobchiModerationClient $client): void
    {
        $comments = BookClubComment::query()
            ->whereNull('parent_id')
            ->where(function ($q) {
                $q->where(function ($a) {
                    $a->whereNull('kangaroo_checked_at');
                })->orWhere(function ($a) {
                    $a->whereColumn('updated_at', '>', 'kangaroo_checked_at')
                        ->whereNotIn('kangaroo_ugc_status', ['pending_admin', 'admin_scored']);
                });
            })
            ->orderByDesc('updated_at')
            ->limit(80)
            ->get(['id', 'post_id', 'content']);

        $posts = BookClub::query()
            ->where('is_deleted', 0)
            ->where(function ($q) {
                $q->where(function ($a) {
                    $a->whereNull('kangaroo_post_checked_at');
                })->orWhere(function ($a) {
                    $a->whereColumn('updated_at', '>', 'kangaroo_post_checked_at')
                        ->whereNotIn('kangaroo_post_ugc_status', ['pending_admin', 'admin_scored']);
                });
            })
            ->orderByDesc('updated_at')
            ->limit(40)
            ->get(['id', 'text']);

        if ($comments->isEmpty() && $posts->isEmpty()) {
            $this->info('UGC: navbat bo‘sh.');

            return;
        }

        try {
            $resp = $client->moderateUgc(
                $posts->map(fn ($p) => ['id' => $p->id, 'text' => $p->text])->values()->all(),
                $comments->map(fn ($c) => ['id' => $c->id, 'post_id' => $c->post_id, 'content' => $c->content])->values()->all(),
            );
        } catch (\Throwable $e) {
            Log::warning('Kangaroo UGC: API xato — pending_admin', ['message' => $e->getMessage()]);
            $this->warn('UGC: Kangaroo javob bermadi — '.$e->getMessage().' (admin navbatiga).');
            $this->markUgcPendingAdmin($comments, $posts);

            return;
        }

        $touchedPostIds = [];

        foreach ($resp['book_club_comments'] ?? [] as $row) {
            $cid = (int) ($row['id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $needs = ! empty($row['needs_admin_review']);
            BookClubComment::query()->where('id', $cid)->update([
                'kangaroo_star_equivalent' => $needs ? null : ($row['star_equivalent'] ?? null),
                'kangaroo_toxicity' => $row['toxicity'] ?? null,
                'kangaroo_ugc_status' => $needs ? 'pending_admin' : 'auto_scored',
                'kangaroo_checked_at' => now(),
            ]);
            $touchedPostIds[] = (int) ($row['post_id'] ?? 0);
        }

        foreach ($resp['book_club_posts'] ?? [] as $row) {
            $pid = (int) ($row['id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $needs = ! empty($row['needs_admin_review']);
            BookClub::query()->where('id', $pid)->update([
                'kangaroo_post_star' => $needs ? null : ($row['star_equivalent'] ?? null),
                'kangaroo_post_ugc_status' => $needs ? 'pending_admin' : 'auto_scored',
                'kangaroo_post_checked_at' => now(),
            ]);
            $touchedPostIds[] = $pid;
        }

        $returnedCids = collect($resp['book_club_comments'] ?? [])->pluck('id')->map(fn ($v) => (int) $v)->filter()->all();
        foreach ($comments as $c) {
            if (! in_array((int) $c->id, $returnedCids, true)) {
                BookClubComment::query()->where('id', $c->id)->update([
                    'kangaroo_star_equivalent' => null,
                    'kangaroo_toxicity' => null,
                    'kangaroo_ugc_status' => 'pending_admin',
                    'kangaroo_checked_at' => now(),
                ]);
                $touchedPostIds[] = (int) $c->post_id;
            }
        }

        $returnedPids = collect($resp['book_club_posts'] ?? [])->pluck('id')->map(fn ($v) => (int) $v)->filter()->all();
        foreach ($posts as $p) {
            if (! in_array((int) $p->id, $returnedPids, true)) {
                BookClub::query()->where('id', $p->id)->update([
                    'kangaroo_post_star' => null,
                    'kangaroo_post_ugc_status' => 'pending_admin',
                    'kangaroo_post_checked_at' => now(),
                ]);
                $touchedPostIds[] = (int) $p->id;
            }
        }

        $touchedPostIds = array_values(array_unique(array_filter($touchedPostIds)));

        foreach ($touchedPostIds as $postId) {
            if ($postId <= 0) {
                continue;
            }
            BookClubUgcSupport::recalcPostStarFromComments($postId);
        }

        BookClubUgcSupport::recalcProductUgcFromPosts($touchedPostIds);

        $this->info('UGC: post '.$posts->count().', izoh '.$comments->count().' jarayonlandi.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, BookClubComment>  $comments
     * @param  \Illuminate\Support\Collection<int, BookClub>  $posts
     */
    private function markUgcPendingAdmin(Collection $comments, Collection $posts): void
    {
        foreach ($comments as $c) {
            BookClubComment::query()->where('id', $c->id)->update([
                'kangaroo_star_equivalent' => null,
                'kangaroo_toxicity' => null,
                'kangaroo_ugc_status' => 'pending_admin',
                'kangaroo_checked_at' => now(),
            ]);
        }
        foreach ($posts as $p) {
            BookClub::query()->where('id', $p->id)->update([
                'kangaroo_post_star' => null,
                'kangaroo_post_ugc_status' => 'pending_admin',
                'kangaroo_post_checked_at' => now(),
            ]);
        }
    }
}
