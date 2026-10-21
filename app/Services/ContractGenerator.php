<?php

namespace App\Services;

use App\Models\ContractFieldValue;
use App\Support\Contracts\ContractTemplates;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

/**
 * Shartnoma shablonlarini (.docx) to'ldiradi: {{kalit}} o'rinbosarlarini boshqaruvda saqlangan
 * qiymatlar, avtomatik sanalar va (ixtiyoriy) kontragent ma'lumotlari bilan almashtiradi.
 */
class ContractGenerator
{
    public const VISIBILITY_KEY = '__public';

    private const CACHE_KEY = 'contracts:values';

    private const MONTHS_UZ = ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentyabr', 'oktyabr', 'noyabr', 'dekabr'];

    private const WEEKDAYS_UZ = [1 => 'dushanba', 'seshanba', 'chorshanba', 'payshanba', 'juma', 'shanba', 'yakshanba'];

    /** Saqlangan qiymatlar: scope => key => value */
    public function stored(): array
    {
        if (! Schema::hasTable('contract_field_values')) {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, 600, function () {
            $out = [];
            foreach (ContractFieldValue::query()->get(['scope', 'key', 'value']) as $row) {
                $out[$row->scope][$row->key] = (string) $row->value;
            }

            return $out;
        });
    }

    public function save(string $scope, array $values, ?int $adminId = null): void
    {
        foreach ($values as $key => $value) {
            $value = is_string($value) ? trim($value) : $value;
            if ($value === null || $value === '') {
                ContractFieldValue::where('scope', $scope)->where('key', $key)->delete();
                continue;
            }
            ContractFieldValue::updateOrCreate(['scope' => $scope, 'key' => $key], ['value' => (string) $value, 'updated_by' => $adminId]);
        }
        Cache::forget(self::CACHE_KEY);
    }

    public function isPublic(string $slug): bool
    {
        return ($this->stored()[$slug][self::VISIBILITY_KEY] ?? '1') === '1';
    }

    /** Saytda ko'rinadigan shablonlar ro'yxati. */
    public function publicTemplates(): array
    {
        $out = [];
        foreach (ContractTemplates::all() as $slug => $t) {
            if ($this->isPublic($slug) && is_file(ContractTemplates::path($slug))) {
                $out[] = ['slug' => $slug, 'title' => $t['title'], 'title_ru' => $t['title_ru'], 'description' => $t['description'], 'audience' => $t['audience']];
            }
        }

        return $out;
    }

    /**
     * Barcha o'rinbosarlar qiymatlari.
     *
     * @param  array<string,string>  $party  kontragent maydonlari (faqat boshqaruvdan)
     */
    public function values(string $slug, ?CarbonImmutable $date = null, array $party = []): array
    {
        $tpl = ContractTemplates::get($slug) ?? throw new RuntimeException('Shablon topilmadi');
        $stored = $this->stored();
        $date ??= CarbonImmutable::now('Asia/Tashkent');

        $v = [];
        foreach (ContractTemplates::companyFields() as $key => $f) {
            $v[$key] = $stored[ContractTemplates::COMPANY_SCOPE][$key] ?? $f['default'];
        }
        foreach ($tpl['terms'] as $key => $f) {
            $v[$key] = $stored[$slug][$key] ?? $f['default'];
        }
        foreach ($tpl['party'] as $key => $f) {
            $given = isset($party[$key]) ? trim((string) $party[$key]) : '';
            $v[$key] = $given !== '' ? $given : $f['default'];
        }

        return $v + $this->dateValues($date);
    }

    public function dateValues(CarbonImmutable $d): array
    {
        $v = [
            'sana' => $d->format('d.m.Y'),
            'sana_kun' => $d->format('d'),
            'sana_oy' => self::MONTHS_UZ[$d->month - 1],
            'sana_yil' => $d->format('Y'),
            'tolov_kuni' => self::WEEKDAYS_UZ[$d->dayOfWeekIso],
            // Skuter: 16 haftalik jadval va shartnoma tugash sanasi (16-hafta oxiri)
            'tugash_sana' => '«'.$d->addDays(16 * 7 - 1)->format('d').'» '.self::MONTHS_UZ[$d->addDays(16 * 7 - 1)->month - 1].' '.$d->addDays(16 * 7 - 1)->format('Y'),
        ];
        for ($w = 1; $w <= 16; $w++) {
            $start = $d->addDays(($w - 1) * 7);
            $v["hafta_{$w}_bosh"] = $start->format('d/m/Y');
            $v["hafta_{$w}_oxir"] = $start->addDays(6)->format('d/m/Y');
        }

        return $v;
    }

    /** To'ldirilgan .docx (ikkilik ma'lumot). */
    public function render(string $slug, array $values): string
    {
        $src = ContractTemplates::path($slug);
        if (! is_file($src)) {
            throw new RuntimeException('Shablon fayli yo\'q: '.$slug);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'ctr');
        copy($src, $tmp);

        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            throw new RuntimeException('Shablonni ochib bo\'lmadi');
        }
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (! preg_match('#^word/(document|header\d*|footer\d*)\.xml$#', $name)) {
                    continue;
                }
                $xml = $zip->getFromName($name);
                $filled = preg_replace_callback('/\{\{([a-z0-9_]+)\}\}/', function ($m) use ($values) {
                    return htmlspecialchars((string) ($values[$m[1]] ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                }, $xml);
                $zip->addFromString($name, $filled);
            }
            $zip->close();
            $data = file_get_contents($tmp);
        } finally {
            @unlink($tmp);
        }

        return $data;
    }

    public function filename(string $slug, CarbonImmutable $date): string
    {
        return ContractTemplates::get($slug)['filename'].'_'.$date->format('Y-m-d').'.docx';
    }
}
