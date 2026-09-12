<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ma'lumotlarni tuzatish migratsiyasi (2026-09-12).
     *
     * Ilgari AI (BookClubContentModerationService) "book_club" va
     * "book_club_comments" jadvallaridagi postlar/kommentariyalarni
     * o'zi mustaqil ravishda yashirishi mumkin edi (is_hidden_by_ai = true,
     * ai_moderation_status = 'hidden'/'clean'). Bu endi taqiqlangan — AI
     * faqat tavsiya bera oladi, yashirish/o'chirish huquqi faqat admin'da.
     *
     * Shu sabab:
     *   1) AI tomonidan avtomatik yashirilgan (ADMIN emas!) barcha yozuvlarni
     *      qayta ko'rinadigan qilamiz (is_hidden_by_ai = false). Admin qo'lda
     *      yashirgan ('manual_hidden') yozuvlarga TEGINMAYMIZ — bu inson
     *      qarori, saqlanib qolishi kerak.
     *   2) Eski AI statuslarini ('hidden'/'clean') yangi lug'atga
     *      ('ai_flagged'/'ai_clean') o'tkazamiz, chunki bu status endi
     *      "ko'rinmas qilindi" emas, faqat "AI tavsiyasi" ma'nosini
     *      anglatadi (BookClubContentModerationService::storeDecision()
     *      bilan mos kelishi uchun).
     *
     * Bu migratsiya #456-postni (Kulrang Ufq) va shunga o'xshash
     * noto'g'ri yashirilgan postlarni tiklaydi.
     */
    public function up(): void
    {
        // --- book_club (postlar) ---
        DB::table('book_club')
            ->where('is_hidden_by_ai', true)
            ->where(function ($q) {
                $q->whereNull('ai_moderation_status')
                    ->orWhereNotIn('ai_moderation_status', ['manual_hidden', 'manual_clean']);
            })
            ->update(['is_hidden_by_ai' => false]);

        DB::table('book_club')
            ->where('ai_moderation_status', 'hidden')
            ->update(['ai_moderation_status' => 'ai_flagged']);

        DB::table('book_club')
            ->where('ai_moderation_status', 'clean')
            ->update(['ai_moderation_status' => 'ai_clean']);

        // --- book_club_comments (kommentariyalar) ---
        DB::table('book_club_comments')
            ->where('is_hidden_by_ai', true)
            ->where(function ($q) {
                $q->whereNull('ai_moderation_status')
                    ->orWhereNotIn('ai_moderation_status', ['manual_hidden', 'manual_clean']);
            })
            ->update(['is_hidden_by_ai' => false]);

        DB::table('book_club_comments')
            ->where('ai_moderation_status', 'hidden')
            ->update(['ai_moderation_status' => 'ai_flagged']);

        DB::table('book_club_comments')
            ->where('ai_moderation_status', 'clean')
            ->update(['ai_moderation_status' => 'ai_clean']);
    }

    /**
     * Bu ma'lumot tuzatish migratsiyasi — orqaga qaytarish operatsiyasi
     * asl "kim yashirgan edi" holatini tiklay olmaydi (bu ma'lumot
     * o'zgartirilgan paytda yo'qotiladi), shu sabab down() qasddan
     * hech narsa qilmaydi.
     */
    public function down(): void
    {
        // Qasddan bo'sh — pastga qarab ma'lumotni ishonchli tiklab
        // bo'lmaydi (yuqoridagi izohga qarang).
    }
};
