<?php

namespace App\Services\Staff;

use Illuminate\Support\Str;

/**
 * Boshqaruv route nomlari → modul (Admin::MODULES kalitlari) va
 * audit log yozuvlarini odam tushunadigan o'zbekcha amal nomiga aylantirish.
 */
class StaffModules
{
    /** Uzunroq prefiks birinchi tekshiriladi. */
    private const PREFIXES = [
        'users.split.' => 'split',
        'support.inbox.' => 'support',
        'seller-support.' => 'support',
        'support.' => 'support',
        'complaints.' => 'support',
        'catalog-slots.' => 'catalog',
        'catalog.' => 'catalog',
        'books.' => 'catalog',
        'stationery.' => 'catalog',
        'authors.' => 'catalog',
        'publishers.' => 'catalog',
        'book-categories.' => 'catalog',
        'stationery-categories.' => 'catalog',
        'orders.' => 'orders',
        'users.' => 'users',
        'split.' => 'split',
        'seller-orders.' => 'sellers',
        'seller-order-items.' => 'sellers',
        'sellers.' => 'sellers',
        'courier-orders.' => 'couriers',
        'couriers.' => 'couriers',
        'hub-applications.' => 'hubs',
        'hubs.' => 'hubs',
        'fiscalization.' => 'finance',
        'expenses.' => 'finance',
        'courier-transactions.' => 'finance',
        'transactions.' => 'finance',
        'logistika.' => 'logistika',
        'home-sections.' => 'marketing',
        'book-videos.' => 'marketing',
        'promokodlar.' => 'marketing',
        'blogerlar.' => 'marketing',
        'ads.' => 'marketing',
        'gift-sertifikatlar.' => 'marketing',
        'market-news.' => 'marketing',
        'collections.' => 'marketing',
        'reels.' => 'marketing',
        'content.' => 'marketing',
        'book-club.' => 'book-club',
        'push.' => 'push',
        'vacancies.' => 'hr',
        'karyera-arizalari.' => 'hr',
        'admins.' => 'admins',
        'mystery-box.' => 'premium',
        'prize-game.' => 'premium',
        'policies.' => 'settings',
        'api-clients.' => 'settings',
        'api-webhooks.' => 'settings',
        'settings.' => 'settings',
        'security.' => 'settings',
        'seller-ai-actions.' => 'seller-ai',
    ];

    public const LABELS = [
        'orders' => 'Buyurtmalar',
        'users' => 'Foydalanuvchilar',
        'split' => 'Split',
        'catalog' => 'Katalog',
        'sellers' => 'Sotuvchilar',
        'couriers' => 'Kuryerlar',
        'hubs' => 'Hub',
        'finance' => 'Moliya',
        'logistika' => 'Logistika',
        'marketing' => 'Marketing',
        'book-club' => 'Book Club',
        'support' => 'Support',
        'push' => 'Push',
        'hr' => 'HR',
        'premium' => 'Premium',
        'settings' => 'Sozlamalar',
        'admins' => 'Adminlar',
        'seller-ai' => 'Seller AI',
        'other' => 'Boshqa',
    ];

    private const VERBS = [
        'approve' => 'tasdiqladi',
        'reject' => 'rad etdi',
        'store' => 'qo‘shdi',
        'create' => 'yaratdi',
        'update' => 'tahrirladi',
        'destroy' => 'o‘chirdi',
        'delete' => 'o‘chirdi',
        'status' => 'holatini o‘zgartirdi',
        'moderate' => 'moderatsiya qildi',
        'moderation' => 'moderatsiya qildi',
        'reply' => 'javob berdi',
        'close' => 'yopdi',
        'assign' => 'biriktirdi',
        'toggle' => 'yoqdi/o‘chirdi',
        'cancel' => 'bekor qildi',
        'refund' => 'pul qaytardi',
        'refund-cancel' => 'pul qaytarib bekor qildi',
        'block' => 'blokladi',
        'unblock' => 'blokdan chiqardi',
        'ban' => 'taqiqladi',
        'unban' => 'taqiqni olib tashladi',
        'warn' => 'ogohlantirdi',
        'verify' => 'tasdiqladi',
        'merge' => 'birlashtirdi',
        'restore' => 'tikladi',
        'archive' => 'arxivladi',
        'resend' => 'qayta yubordi',
        'retry' => 'qayta urindi',
        'register' => 'ro‘yxatdan o‘tkazdi',
        'sync' => 'sinxronladi',
        'release' => 'bo‘shatdi',
        'penalty' => 'jarima qo‘ydi',
        'reset-password' => 'parolini tikladi',
        'settle' => 'yopdi',
        'charge' => 'yechdi',
        'bulk' => 'ommaviy amal bajardi',
        'premium' => 'premium berdi',
        'reassign-seller' => 'do‘konini almashtirdi',
        'switch-mode' => 'yetkazish rejimini o‘zgartirdi',
        'reroute-hub' => 'hubini o‘zgartirdi',
        'pay-pending-card' => 'karta to‘lovini o‘tkazdi',
        'send-unreachable-push' => 'mijozga push yubordi',
        'templates' => 'shablonni o‘zgartirdi',
        'settings' => 'sozlamani o‘zgartirdi',
    ];

    private const OBJECTS = [
        'orders' => 'buyurtmani',
        'users' => 'foydalanuvchini',
        'split' => 'split shartnomasini',
        'catalog' => 'katalogni',
        'books' => 'kitobni',
        'stationery' => 'kanselyariyani',
        'authors' => 'muallifni',
        'publishers' => 'nashriyotni',
        'sellers' => 'do‘konni',
        'seller-orders' => 'do‘kon buyurtmasini',
        'couriers' => 'kuryerni',
        'courier-orders' => 'kuryer buyurtmasini',
        'hubs' => 'hubni',
        'hub-applications' => 'hub arizasini',
        'transactions' => 'pul yechish so‘rovini',
        'courier-transactions' => 'kuryer to‘lovini',
        'expenses' => 'chiqimni',
        'fiscalization' => 'fiskal chekni',
        'support' => 'murojaatni',
        'seller-support' => 'do‘kon murojaatini',
        'complaints' => 'shikoyatni',
        'karyera-arizalari' => 'arizani',
        'vacancies' => 'vakansiyani',
        'push' => 'push xabarni',
        'promokodlar' => 'promokodni',
        'ads' => 'reklamani',
        'book-club' => 'Book Club postini',
    ];

    public static function moduleOf(?string $routeName): string
    {
        $name = Str::after((string) $routeName, 'boshqaruv.');
        foreach (self::PREFIXES as $prefix => $module) {
            if (str_starts_with($name, $prefix)) {
                return $module;
            }
        }

        return 'other';
    }

    public static function label(string $module): string
    {
        return self::LABELS[$module] ?? Str::headline($module);
    }

    /**
     * "orders.status" + target 123 → "Buyurtmani holatini o‘zgartirdi · #123"
     */
    public static function describe(?string $routeName, ?string $method, ?string $targetId = null): string
    {
        $name = Str::after((string) $routeName, 'boshqaruv.');
        if ($name === '') {
            return strtoupper((string) $method) . ' so‘rov';
        }

        $parts = explode('.', $name);
        $verbKey = end($parts);
        $verb = self::VERBS[$verbKey] ?? null;
        if (! $verb && count($parts) > 1) {
            $verb = self::VERBS[$parts[count($parts) - 2]] ?? null;
        }
        $verb ??= match (strtoupper((string) $method)) {
            'DELETE' => 'o‘chirdi',
            'POST' => 'qo‘shdi',
            default => 'o‘zgartirdi',
        };

        $object = self::OBJECTS[$parts[0]] ?? (self::OBJECTS[$parts[0] . '-' . ($parts[1] ?? '')] ?? null);
        $object ??= mb_strtolower(self::label(self::moduleOf($routeName)));

        $text = Str::ucfirst($object . ' ' . $verb);

        return $targetId ? $text . ' · #' . $targetId : $text;
    }
}
