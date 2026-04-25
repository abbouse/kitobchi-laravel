<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sellers.contract_signed — shartnoma imzolanganligini eksplitsit boolean.
 *
 * Avvalgi migrationda contract_signed_at (date) bor edi: agar to'ldirilsa,
 * implitsit ravishda "imzolangan" deb tushuniladi. Lekin admin paneldan
 * tezda toggle qilish (sana bilmasa ham) yoki "imzolangan, sanani keyin
 * kiritamiz" holatlari uchun alohida boolean kerak.
 *
 * Logika:
 *   - false (default) — shartnoma hali imzolanmagan (ariza qabul qilingan,
 *     lekin shartnoma jarayoni boshlanmagan)
 *   - true            — shartnoma imzolangan (sana bilan yoki sanasiz)
 *
 * Eski sellerlarda contract_signed_at to'la bo'lsa — migration shu sellerlar
 * uchun contract_signed = true qilib qo'yadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->boolean('contract_signed')
                  ->default(false)
                  ->after('contract_status');

            $table->index('contract_signed');
        });

        // Eski sellerlarda contract_signed_at bor bo'lsa — true qilib qo'yamiz
        \DB::table('sellers')
            ->whereNotNull('contract_signed_at')
            ->update(['contract_signed' => true]);
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropIndex(['contract_signed']);
            $table->dropColumn('contract_signed');
        });
    }
};
