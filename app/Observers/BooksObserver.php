<?php

namespace App\Observers;

use App\Models\Books;
use App\Services\GoogleIndexingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BooksObserver
{
    public function saved(Books $book): void
    {
        Cache::forget('seo_sitemap_xml');

        if ($book->status && $book->is_approved && ! $book->is_hidden) {
            $url = route('web.books.show', ['id' => $book->id, 'slug' => Str::slug($book->name)]);
            app(GoogleIndexingService::class)->notifyUrl($url, 'URL_UPDATED');
        }
    }

    public function deleted(Books $book): void
    {
        Cache::forget('seo_sitemap_xml');

        $url = route('web.books.show', ['id' => $book->id, 'slug' => Str::slug($book->name)]);
        app(GoogleIndexingService::class)->notifyUrl($url, 'URL_DELETED');
    }
}
