<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `users.status` ustuni ilgari deyarli hech qachon aniq to'ldirilmagan —
 * amalda faqat "blocked" qiymatiga o'rnatilardi (A122\UserController::block),
 * aks holda har doim NULL bo'lib qolardi (oddiy SMS ro'yxatdan o'tishda ham,
 * Telegram orqali login/ro'yxatdan o'tishda ham status hech qachon
 * yozilmagan). Endi Telegram orqali login qilganda status avtomatik
 * "active" qilib qo'yiladi (App\Http\Controllers\Api\AuthController::
 * resolveTelegramUser). Shu bilan bir xillikni saqlash uchun — mavjud
 * (eski) foydalanuvchilarning ham statusini shu migratsiya bilan "active"
 * qilib qo'yamiz.
 *
 * MUHIM: hozirda bloklangan (`status = 'blocked'`) foydalanuvchilarga
 * TEGINILMAYDI — aks holda bu migratsiya ularni tasodifan blokdan
 * chiqarib yuborgan bo'lardi. Faqat status NULL yoki bo'sh qator
 * ("") bo'lgan foydalanuvchilar "active" qilinadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '');
            })
            ->update(['status' => 'active']);
    }

    public function down(): void
    {
        // Qaysi qatorlar oldin NULL bo'lgani haqida ma'lumot saqlanmagan,
        // shuning uchun to'g'ri orqaga qaytarib bo'lmaydi — xavfsiz no-op.
        // (status='blocked' bo'lganlarga bu migratsiya umuman tegmagan edi.)
    }
};
