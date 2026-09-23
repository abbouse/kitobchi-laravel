<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Books;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthorDirectoryService
{
    private const BOOK_UZ_API = 'https://backend.book.uz/user-api/author';

    public function resolveOrCreateByName(?string $name, array $attributes = []): ?Author
    {
        $name = $this->cleanName($name);
        if ($name === null) {
            return null;
        }

        $query = Author::query();

        if (! empty($attributes['external_id'])) {
            $query->where('external_id', (string) $attributes['external_id']);
            $author = $query->first();
            if ($author) {
                return tap($author)->update($this->filteredAttributes($name, $attributes));
            }
        }

        $author = Author::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($author) {
            $author->update($this->filteredAttributes($name, $attributes));
            return $author;
        }

        return Author::query()->create($this->filteredAttributes($name, $attributes));
    }

    public function syncBookUzAuthors(?int $limit = 24000): array
    {
        @set_time_limit(0);

        $payload = Http::timeout(120)
            ->retry(2, 1000)
            ->acceptJson()
            ->get(self::BOOK_UZ_API, [
                'page' => 1,
                'limit' => $limit ?: 24000,
            ])
            ->throw()
            ->json();

        $items = Arr::get($payload, 'data.data', []);
        $synced = 0;
        $created = 0;
        $updated = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = $this->cleanName($item['fullName'] ?? $item['name'] ?? null);
            if ($name === null) {
                continue;
            }

            $existing = null;
            $externalId = (string) ($item['_id'] ?? $item['id'] ?? '');
            if ($externalId !== '') {
                $existing = Author::query()->where('external_id', $externalId)->first();
            }
            if (! $existing) {
                $existing = Author::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->first();
            }

            $attributes = $this->filteredAttributes($name, [
                'external_id' => $externalId !== '' ? $externalId : null,
                'slug' => $item['link'] ?? null,
                'image' => $item['imgUrl'] ?? null,
                'source_url' => ! empty($item['link']) ? ('https://book.uz/authors?author=' . $item['link']) : null,
            ]);

            if ($existing) {
                $existing->update($attributes);
                $updated++;
            } else {
                Author::query()->create($attributes);
                $created++;
            }

            $synced++;
        }

        return compact('synced', 'created', 'updated');
    }

    public function backfillBooks(?int $limit = null): array
    {
        @set_time_limit(0);

        if (! Books::hasAuthorColumn()) {
            return [
                'linked' => 0,
                'createdAuthors' => 0,
            ];
        }

        $query = Books::query()
            ->whereNotNull('author')
            ->where('author', '!=', '');

        if ($limit) {
            $query->limit($limit);
        }

        $linked = 0;
        $createdAuthors = 0;

        $query->orderBy('id')->chunkById(200, function ($books) use (&$linked, &$createdAuthors) {
            foreach ($books as $book) {
                $beforeAuthorId = $book->author_id;
                $beforeCount = Author::count();

                $author = $this->resolveOrCreateByName($book->getRawOriginal('author'));
                if (! $author) {
                    continue;
                }

                if ($beforeCount !== Author::count()) {
                    $createdAuthors++;
                }

                if ((int) $beforeAuthorId !== (int) $author->id || $book->getRawOriginal('author') !== $author->name) {
                    // GLOBAL KATALOG: ulangan taklifda muallif kartadan olinadi —
                    // shuning uchun avval karta to'ldiriladi, keyin taklifga ko'chadi.
                    if ($book->edition_id) {
                        \App\Models\BookEdition::query()
                            ->whereKey($book->edition_id)
                            ->update(['author_id' => $author->id, 'author' => $author->name]);
                        Books::writingFromCatalog(fn () => Books::query()
                            ->whereKey($book->id)
                            ->toBase()
                            ->update(['author_id' => $author->id, 'author' => $author->name, 'vector_text_hash' => null]));
                        $linked++;

                        continue;
                    }

                    $book->forceFill([
                        'author_id' => $author->id,
                        'author' => $author->name,
                    ])->save();
                    $linked++;
                }
            }
        });

        return compact('linked', 'createdAuthors');
    }

    private function filteredAttributes(string $name, array $attributes = []): array
    {
        $image = $attributes['image'] ?? null;
        if (is_string($image) && trim($image) !== '' && ! str_starts_with($image, 'http')) {
            $image = 'https://backend.book.uz/user-api/' . ltrim($image, '/');
        }

        return array_filter([
            'name' => $name,
            'external_id' => $attributes['external_id'] ?? null,
            'slug' => $attributes['slug'] ?? null,
            'image' => $image,
            'source_url' => $attributes['source_url'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function cleanName(?string $name): ?string
    {
        $value = trim((string) $name);
        return $value === '' ? null : Str::limit($value, 255, '');
    }
}
