<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RolePreset extends Model
{
    protected $fillable = [
        'emoji',
        'title_uz',
        'title_ru',
        'title_en',
        'title_ja',
        'needs_place',
        'sort'
    ];

    protected static function booted(): void
    {
        $flush = fn () => \Illuminate\Support\Facades\Cache::forget('role_presets:title_map');
        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Foydalanuvchida saqlangan rol nomini joriy tilga o'giradi.
     *
     * Rol tanlanganda nom foydalanuvchining O'Z tilida saqlanadi (masalan
     * "O'qituvchi") — boshqa tildagi o'quvchilar uni tarjimasiz ko'rardi.
     * Saqlangan nom qaysi tilda bo'lishidan qat'i nazar, mos preset topilsa
     * so'ralgan tildagi nomi qaytariladi; qo'lda yozilgan rol o'zgarmaydi.
     */
    public static function localizeTitle(?string $title, ?string $locale = null): ?string
    {
        $title = $title === null ? null : trim($title);
        if ($title === null || $title === '') {
            return $title;
        }
        $map = \Illuminate\Support\Facades\Cache::remember('role_presets:title_map', 3600, function () {
            $out = [];
            foreach (self::query()->get(['title_uz', 'title_ru', 'title_en', 'title_ja']) as $p) {
                $row = ['uz' => $p->title_uz, 'ru' => $p->title_ru, 'en' => $p->title_en, 'ja' => $p->title_ja];
                foreach ($row as $t) {
                    if ($t !== null && trim($t) !== '') {
                        $out[mb_strtolower(trim($t))] = $row;
                    }
                }
            }

            return $out;
        });
        $row = $map[mb_strtolower($title)] ?? null;
        if (! $row) {
            return $title;
        }
        $locale = in_array($locale ??= app()->getLocale(), ['uz', 'ru', 'en', 'ja'], true) ? $locale : 'uz';

        return ($row[$locale] ?? '') !== '' ? $row[$locale] : $title;
    }
}