<?php

namespace App\Observers;

use App\Models\Stationery;
use App\Services\GoogleIndexingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StationeryObserver
{
    public function saved(Stationery $item): void
    {
        Cache::forget('seo_sitemap_xml');

        if ($item->status && $item->is_approved && ! $item->is_hidden) {
            $url = route('web.stationery.show', ['id' => $item->id, 'slug' => Str::slug($item->name)]);
            app(GoogleIndexingService::class)->notifyUrl($url, 'URL_UPDATED');
        }
    }

    public function deleted(Stationery $item): void
    {
        Cache::forget('seo_sitemap_xml');

        $url = route('web.stationery.show', ['id' => $item->id, 'slug' => Str::slug($item->name)]);
        app(GoogleIndexingService::class)->notifyUrl($url, 'URL_DELETED');
    }
}
