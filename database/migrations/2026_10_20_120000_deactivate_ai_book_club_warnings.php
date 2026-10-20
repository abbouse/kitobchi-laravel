<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Book Club postlari endi AI tomonidan baholanmaydi va jazolanmaydi:
 * AI qo'ygan (admin_id bo'lmagan) faol ogohlantirishlar o'chiriladi —
 * shu sabab yozish yopilgan foydalanuvchilar avtomatik ochiladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('book_club_warnings')) {
            return;
        }

        DB::table('book_club_warnings')
            ->whereNull('admin_id')
            ->where('is_active', true)
            ->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Qaytarilmaydi: AI ogohlantirishlari qayta yoqilmaydi.
    }
};
