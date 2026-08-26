<?php

namespace App\Console\Commands;

use App\Models\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * QORALAMA kolleksiyani (collections:generate-drafts orqali yaratilgan)
 * intro matni bilan to'ldirib, e'lon qiladi (is_active=true). Faqat
 * shundan keyin sitemap.xml va /kolleksiya/{slug} orqali ko'rinadi.
 */
class PublishCollection extends Command
{
    protected $signature = 'collections:publish {slug} {--intro=} {--title=}';
    protected $description = 'Kolleksiyaga intro matn yozib, is_active=true qilib e\'lon qiladi';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $collection = Collection::where('slug', $slug)->first();

        if (! $collection) {
            $this->error("Kolleksiya topilmadi: {$slug}");

            return self::FAILURE;
        }

        $intro = $this->option('intro');
        if ($intro) {
            $collection->intro = $intro;
        }

        $title = $this->option('title');
        if ($title) {
            $collection->title = $title;
        }

        if (! $collection->intro) {
            $this->error("Intro matni yo'q — --intro=\"...\" bilan bering (soxta/bo'sh kontent nashr qilinmasin).");

            return self::FAILURE;
        }

        $collection->is_active = true;
        $collection->save();

        Cache::forget('collections_index_v1');

        $this->info("E'lon qilindi: /kolleksiya/{$slug}");

        return self::SUCCESS;
    }
}
