<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * GLOBAL KATALOG: bitta muqova fayli katalog kartasi va bir nechta do'kon
 * taklifida umumiy bo'ladi. Taklifdan rasm olib tashlanganda fayl faqat
 * boshqa hech kim ishlatmasa o'chiriladi.
 */
final class SharedImageGuard
{
    public static function canDelete(string $path, ?int $exceptBookId = null): bool
    {
        $path = trim($path);
        if ($path === '') {
            return false;
        }

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $path) . '%';

        $usedByBooks = DB::table('books')
            ->when($exceptBookId, fn ($q) => $q->where('id', '!=', $exceptBookId))
            ->where('images', 'like', $like)
            ->exists();
        if ($usedByBooks) {
            return false;
        }

        return ! DB::table('book_editions')
            ->where(fn ($q) => $q->where('front_image', $path)->orWhere('back_image', $path)->orWhere('images', 'like', $like))
            ->exists();
    }
}
