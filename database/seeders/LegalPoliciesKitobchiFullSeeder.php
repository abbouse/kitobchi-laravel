<?php

namespace Database\Seeders;

use App\Models\Policy;
use App\Models\PolicyTranslation;
use Illuminate\Database\Seeder;

/**
 * Kitobchi huquqiy hujjatlari (policies + policy_translations).
 * Wildberries UZ isteʼmolchi ofertasi tuzilmasiga oʻxshash bandlar; farq: faqat Oʻzbekiston,
 * chet eldan import yoʻq; oddiy xarid boʻyicha tovar qaytarilmaydi.
 *
 * Ishlatish: php artisan db:seed --class=LegalPoliciesKitobchiFullSeeder
 */
class LegalPoliciesKitobchiFullSeeder extends Seeder
{
    public function run(): void
    {
        $pack = require database_path('data/legal_policies_kitobchi.php');

        foreach ($pack['slugs_to_replace'] as $slug) {
            $ids = Policy::query()->where('slug', $slug)->pluck('id');
            PolicyTranslation::query()->whereIn('policy_id', $ids)->delete();
            Policy::query()->where('slug', $slug)->delete();
        }

        foreach ($pack['policies'] as $row) {
            $policy = Policy::query()->create([
                'title' => $row['title_uz'],
                'slug' => $row['slug'],
                'content' => $row['content_uz'],
                'sort_order' => (int) $row['sort_order'],
                'is_active' => true,
                'show_in_app' => true,
            ]);

            PolicyTranslation::query()->create([
                'policy_id' => $policy->id,
                'locale' => 'en',
                'title' => $row['title_en'],
                'content' => $row['content_en'],
            ]);
        }
    }
}
