<?php

namespace App\Services\Catalog;

use App\Models\BookEdition;
use App\Models\BookEditionSubmission;
use App\Models\Books;
use App\Support\Isbn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Global katalog: kitob kartalari (book_editions) va do'kon takliflari
 * (books) o'rtasidagi bog'lanishning YAGONA manbasi.
 *
 * Qoidalar:
 *  - Kitob kartasi ISBN-13 bo'yicha topiladi; bitta ISBN bir nechta kartaga
 *    tegishli bo'lishi mumkin (O'zbekistonda ISBN qayta ishlatilishi uchraydi),
 *    shuning uchun taklif faqat NOMI HAM mos kartaga ulanadi.
 *  - ISBN'siz kitoblar faqat aniq kalit (nom + muallif + til + yozuv + muqova +
 *    nashriyot) bo'yicha birlashadi — shubhali holatlar alohida karta bo'ladi,
 *    admin keyin "Birlashtirish" orqali qo'shadi.
 *  - Karta ma'lumoti takliflarga faqat syncOffers() orqali ko'chiriladi —
 *    model hodisalarisiz (moderatsiya qayta ishga tushmaydi).
 */
class CatalogService
{
    /** Kartadan taklifga ko'chiriladigan `books` ustunlari. */
    public const OFFER_METADATA_COLUMNS = [
        'name', 'author', 'author_id', 'translator', 'isbn', 'publisher_id',
        'category_id', 'lang', 'langType', 'coverType', 'year', 'pages',
        'description', 'images',
    ];

    public function __construct(private readonly BuyBoxService $buyBox)
    {
    }

    // ── Normallashtirish ────────────────────────────────────────────────

    public static function normalizeText(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        // O'zbek apostroflari: o‘ g‘ o' oʻ — hammasi bitta ko'rinishga
        $value = str_replace(['‘', '’', 'ʻ', 'ʼ', '`', '´', "'"], '', $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    public static function titlesSimilar(?string $a, ?string $b): bool
    {
        $a = self::normalizeText($a);
        $b = self::normalizeText($b);
        if ($a === '' || $b === '') {
            return false;
        }
        if ($a === $b || str_contains($a, $b) || str_contains($b, $a)) {
            return true;
        }

        similar_text($a, $b, $percent);

        return $percent >= 72;
    }

    public static function matchKey(?string $title, ?string $author, ?string $lang, ?string $langType, ?string $coverType, $publisherId): ?string
    {
        $title = self::normalizeText($title);
        if ($title === '') {
            return null;
        }

        return sha1(implode('|', [
            $title,
            self::normalizeText($author),
            self::normalizeText($lang),
            self::normalizeText($langType),
            self::normalizeText($coverType),
            (int) $publisherId,
        ]));
    }

    public static function offerMatchKey(Books $book): ?string
    {
        return self::matchKey(
            $book->name,
            $book->getAttributes()['author'] ?? null,
            $book->lang,
            $book->langType,
            $book->coverType,
            $book->publisher_id,
        );
    }

    // ── Qidiruv ─────────────────────────────────────────────────────────

    /** Birlashtirilgan kartadan oxirgi (amaldagi) kartaga o'tadi. */
    public function resolve(?BookEdition $edition): ?BookEdition
    {
        $guard = 0;
        while ($edition && $edition->status === BookEdition::STATUS_MERGED && $edition->merged_into_id && $guard++ < 5) {
            $edition = BookEdition::withTrashed()->find($edition->merged_into_id);
        }

        return $edition && ! $edition->trashed() ? $edition : null;
    }

    /** @return Collection<int, BookEdition> */
    public function findByIsbn(?string $raw): Collection
    {
        $isbn13 = Isbn::toIsbn13($raw);
        if ($isbn13 === null) {
            return collect();
        }

        return BookEdition::query()
            ->usable()
            ->where('isbn13', $isbn13)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('offers_count')
            ->orderBy('id')
            ->get();
    }

    /** Nom / muallif / ISBN bo'yicha qidiruv (do'kon va admin uchun). */
    public function search(string $query, int $limit = 20): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        if ($isbn13 = Isbn::toIsbn13($query)) {
            return $this->findByIsbn($isbn13)->take($limit);
        }

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query) . '%';

        return BookEdition::query()
            ->usable()
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('author', 'like', $like))
            ->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [str_replace('%', '', $like) . '%'])
            ->orderByDesc('offers_count')
            ->limit($limit)
            ->get();
    }

    // ── Taklifni kartaga ulash ──────────────────────────────────────────

    /**
     * Taklifni mos kartaga ulaydi, topilmasa taklif ma'lumotidan yangi karta
     * ochadi. Taklif ma'lumoti (nom, rasm) O'ZGARTIRILMAYDI.
     */
    public function linkOffer(Books $book, string $source = 'legacy'): ?BookEdition
    {
        if ($book->edition_id) {
            return BookEdition::find($book->edition_id);
        }

        $edition = $this->findMatchingEdition($book) ?? $this->createEditionFromOffer($book, $source);
        $this->attachOffer($book, $edition);

        return $edition;
    }

    public function findMatchingEdition(Books $book): ?BookEdition
    {
        $isbn13 = Isbn::toIsbn13($book->isbn);
        if ($isbn13 !== null) {
            return $this->findByIsbn($isbn13)
                ->first(fn (BookEdition $e) => self::titlesSimilar($book->name, $e->title));
        }

        $key = self::offerMatchKey($book);
        if ($key === null) {
            return null;
        }

        return BookEdition::query()
            ->usable()
            ->whereNull('isbn13')
            ->where('match_key', $key)
            ->orderBy('id')
            ->first();
    }

    public function createEditionFromOffer(Books $book, string $source, ?string $status = null): BookEdition
    {
        $isbn13 = Isbn::toIsbn13($book->isbn);
        $images = is_array($book->images) ? array_values(array_filter($book->images, 'is_string')) : [];

        return BookEdition::create([
            'isbn13' => $isbn13,
            'isbn10' => Isbn::toIsbn10($isbn13),
            'title' => (string) $book->name,
            'author' => $book->getAttributes()['author'] ?? $book->author,
            'author_id' => $book->author_id,
            'translator' => $book->translator,
            'publisher_id' => $book->publisher_id,
            'category_id' => $book->category_id,
            'lang' => $book->lang,
            'langType' => $book->langType,
            'coverType' => $book->coverType,
            'year' => $book->year ?: null,
            'pages' => $book->pages ?: null,
            'description' => $book->description,
            'front_image' => $images[0] ?? null,
            'images' => $images,
            'tag_ids' => $book->relationLoaded('tags')
                ? $book->tags->pluck('id')->all()
                : DB::table('book_tag_relations')->where('book_id', $book->id)->pluck('tag_id')->all(),
            'status' => $status ?? ((int) $book->is_approved === 1 ? BookEdition::STATUS_ACTIVE : BookEdition::STATUS_PENDING),
            'source' => $source,
            'created_by_type' => 'seller',
            'created_by_id' => $book->seller_id,
            'match_key' => self::offerMatchKey($book),
        ]);
    }

    public function attachOffer(Books $book, BookEdition $edition): void
    {
        $oldEditionId = $book->edition_id;

        // Model hodisalarisiz: moderatsiya observer'i ishga tushmasin
        Books::query()->whereKey($book->id)->toBase()->update(['edition_id' => $edition->id]);
        $book->setAttribute('edition_id', $edition->id);
        $book->syncOriginalAttribute('edition_id');

        if ($oldEditionId && (int) $oldEditionId !== (int) $edition->id) {
            $this->buyBox->touch((int) $oldEditionId);
        }
        $this->buyBox->touch((int) $edition->id);
    }

    // ── Kartadan takliflarga ma'lumot ko'chirish ─────────────────────────

    /** Karta ma'lumotidan `books` ustunlari (yangi taklif yaratish va sinxron uchun). */
    public function offerAttributes(BookEdition $edition): array
    {
        $images = array_values(array_filter((array) ($edition->images ?? []), 'is_string'));
        if ($edition->front_image && ! in_array($edition->front_image, $images, true)) {
            array_unshift($images, $edition->front_image);
        }

        return [
            'name' => $edition->title,
            'author' => $edition->author ?: ($edition->authorProfile?->name ?? ''),
            'author_id' => $edition->author_id,
            'translator' => $edition->translator,
            'isbn' => $edition->isbn13,
            'publisher_id' => $edition->publisher_id,
            'category_id' => $edition->category_id,
            'lang' => $edition->lang ?: "O'zbek",
            'langType' => $edition->langType ?: 'Lotin',
            'coverType' => $edition->coverType ?: 'Yumshoq',
            'year' => $edition->year ?: (int) now()->year,
            'pages' => $edition->pages ?: 0,
            'description' => (string) ($edition->description ?? ''),
            'images' => $images,
        ];
    }

    /**
     * Karta ma'lumotini uning takliflariga ko'chiradi (hodisalarsiz), teglarni
     * sinxronlaydi va qidiruv indeksini yangilaydi.
     *
     * @param  array<int>|null  $bookIds  faqat shu takliflar (null — hammasi)
     */
    public function syncOffers(BookEdition $edition, ?array $bookIds = null): int
    {
        $attributes = $this->offerAttributes($edition);
        $row = $attributes;
        $row['images'] = json_encode($attributes['images'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row['updated_at'] = now();
        // Vektor matni o'zgardi — scheduler qayta embed qiladi
        $row['vector_text_hash'] = null;

        $query = Books::query()->where('edition_id', $edition->id);
        if ($bookIds !== null) {
            $query->whereIn('id', $bookIds);
        }
        $ids = $query->pluck('id')->all();
        if (empty($ids)) {
            return 0;
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            Books::query()->whereIn('id', $chunk)->toBase()->update($row);
        }

        $tagIds = array_values(array_unique(array_map('intval', (array) ($edition->tag_ids ?? []))));
        if (! empty($tagIds)) {
            DB::table('book_tag_relations')->whereIn('book_id', $ids)->delete();
            $now = now();
            $rows = [];
            foreach ($ids as $bookId) {
                foreach ($tagIds as $tagId) {
                    $rows[] = ['book_id' => $bookId, 'tag_id' => $tagId];
                }
            }
            foreach (array_chunk($rows, 1000) as $chunk) {
                DB::table('book_tag_relations')->insert($this->withTimestampsIfNeeded($chunk, $now));
            }
        }

        $this->buyBox->reindex($ids);

        return count($ids);
    }

    private ?bool $tagTimestamps = null;

    private function withTimestampsIfNeeded(array $rows, $now): array
    {
        $this->tagTimestamps ??= \Illuminate\Support\Facades\Schema::hasColumn('book_tag_relations', 'created_at');
        if (! $this->tagTimestamps) {
            return $rows;
        }

        return array_map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], $rows);
    }

    // ── Birlashtirish ───────────────────────────────────────────────────

    /**
     * $from kartani $into ga birlashtiradi: takliflar, arizalar ko'chadi,
     * $from "merged" bo'lib qoladi (eski havolalar yo'naltiriladi).
     */
    public function merge(BookEdition $from, BookEdition $into, bool $syncMetadata = true): int
    {
        if ($from->id === $into->id) {
            return 0;
        }

        return DB::transaction(function () use ($from, $into, $syncMetadata) {
            $bookIds = Books::query()->where('edition_id', $from->id)->pluck('id')->all();

            if (! empty($bookIds)) {
                Books::query()->whereIn('id', $bookIds)->toBase()->update(['edition_id' => $into->id]);
            }
            BookEditionSubmission::query()->where('edition_id', $from->id)->update(['edition_id' => $into->id]);
            BookEdition::withTrashed()->where('merged_into_id', $from->id)->update(['merged_into_id' => $into->id]);

            $from->forceFill([
                'status' => BookEdition::STATUS_MERGED,
                'merged_into_id' => $into->id,
            ])->save();

            // Kartada bo'lmagan ma'lumotni birlashtirilayotgan kartadan olamiz
            $fill = [];
            foreach (['isbn13', 'isbn10', 'author_id', 'publisher_id', 'category_id', 'translator', 'year', 'pages', 'description', 'front_image', 'back_image'] as $column) {
                if (blank($into->{$column}) && filled($from->{$column})) {
                    $fill[$column] = $from->{$column};
                }
            }
            if (empty($into->images) && ! empty($from->images)) {
                $fill['images'] = $from->images;
            }
            if (! empty($fill)) {
                $into->forceFill($fill)->save();
            }

            if ($syncMetadata && ! empty($bookIds)) {
                $this->syncOffers($into->fresh(), $bookIds);
            }

            $this->buyBox->touch((int) $from->id);
            $this->buyBox->touch((int) $into->id);

            Log::info('Catalog: editions merged', ['from' => $from->id, 'into' => $into->id, 'offers' => count($bookIds)]);

            return count($bookIds);
        });
    }

    public static function slugTitle(BookEdition $edition): string
    {
        return Str::limit(Str::slug($edition->title), 80, '');
    }
}
