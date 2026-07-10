<?php

/**
 * OFD IKPU yordamchisi — barcha kategoriyalar uchun tasnif.soliq.uz dan
 * IKPU kod nomzodlarini topadi.
 *
 * Ishlatish (VPS da, loyiha papkasida):
 *   php artisan tinker scripts/ofd_ikpu_suggest.php
 *
 * Natija: har bir kategoriya uchun top-3 nomzod kod + nomi chiqadi.
 * Chiqishni ko'chirib olib, mos kodni tanlang va kategoriyaga yozing.
 */

use Illuminate\Support\Facades\Http;

// tasnif.soliq.uz turli versiyalarda turli endpoint ishlatgan —
// birinchi ishlaganini avtomatik tanlaymiz
$endpoints = [
    'https://tasnif.soliq.uz/api/cls-api/mxik/search/by-params',
    'https://tasnif.soliq.uz/api/cls-api/mxik/search',
    'https://tasnif.soliq.uz/api/cls-api/mxik/search-subposition',
];

$searchTasnif = function (string $query) use ($endpoints): array {
    foreach ($endpoints as $endpoint) {
        foreach ([['text' => $query], ['search' => $query], ['params' => $query]] as $paramSet) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get($endpoint, $paramSet + ['size' => 5, 'page' => 0, 'lang' => 'uz']);

                if (! $response->successful()) {
                    continue;
                }

                $json = $response->json();
                if (! is_array($json)) {
                    continue;
                }

                // Turli javob shakllarini qamrab olamiz
                $rows = data_get($json, 'data.content')
                    ?? data_get($json, 'content')
                    ?? data_get($json, 'data')
                    ?? (isset($json[0]) ? $json : null);

                if (! is_array($rows) || $rows === []) {
                    continue;
                }

                $out = [];
                foreach (array_slice($rows, 0, 5) as $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $code = $row['mxikCode'] ?? $row['pkey'] ?? $row['code'] ?? $row['mxik_code'] ?? null;
                    $name = $row['name'] ?? $row['mxikName'] ?? $row['groupName'] ?? $row['subPositionName'] ?? '';

                    if ($code) {
                        $out[] = ['code' => (string) $code, 'name' => mb_substr((string) $name, 0, 90)];
                    }
                }

                if ($out !== []) {
                    return ['endpoint' => $endpoint, 'items' => $out];
                }
            } catch (\Throwable $e) {
                // keyingi variantga o'tamiz
            }
        }
    }

    return ['endpoint' => null, 'items' => []];
};

// ── Kategoriyalarni yig'ish ─────────────────────────────────────────────
$targets = [];

foreach (\App\Models\BookCategories::query()->orderBy('id')->get() as $cat) {
    $targets[] = [
        'table' => 'book_categories',
        'id' => $cat->id,
        'name' => $cat->name_uz,
        'query' => 'kitob', // kitoblar uchun umumiy qidiruv yetarli
        'current' => $cat->ofd_ikpu_code,
    ];
}

foreach (\App\Models\StationeryCategory::query()->orderBy('id')->get() as $cat) {
    $targets[] = [
        'table' => 'stationery_categories',
        'id' => $cat->id,
        'name' => $cat->name_uz,
        'query' => (string) $cat->name_uz, // kanselyariyada kategoriya nomi bilan qidiramiz
        'current' => $cat->ofd_ikpu_code,
    ];
}

echo "Jami kategoriyalar: " . count($targets) . "\n";
echo str_repeat('=', 70) . "\n";

$cache = [];

foreach ($targets as $t) {
    $label = "[{$t['table']} #{$t['id']}] {$t['name']}";
    $current = $t['current'] ? " (joriy: {$t['current']})" : '';
    echo "\n{$label}{$current}\n";

    $q = trim($t['query']);
    if ($q === '') {
        echo "  - nomi bo'sh, o'tkazib yuborildi\n";
        continue;
    }

    if (! isset($cache[$q])) {
        $cache[$q] = $searchTasnif($q);
        usleep(400_000); // tasnif'ni bombardimon qilmaslik uchun
    }

    $result = $cache[$q];

    if ($result['items'] === []) {
        echo "  - topilmadi (qo'lda tasnif.soliq.uz da qidiring: \"{$q}\")\n";
        continue;
    }

    foreach ($result['items'] as $item) {
        echo "  {$item['code']}  {$item['name']}\n";
    }
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "Kodni tanlagach kategoriyaga yozish namunasi:\n";
echo "  \\App\\Models\\StationeryCategory::whereKey(ID)->update(['ofd_ikpu_code' => 'KOD', 'ofd_package_code' => 'QADOQ_KODI']);\n";
echo "Eslatma: qadoq kodini tasnif'dagi kod kartochkasidan oling (har IKPU ning o'z qadoq ro'yxati bor).\n";
