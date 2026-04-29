<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * conversations.type ustuni eski enum'i: enum('personal','shop')
 *
 * Direct messages bo'limida guruh yaratish (ChatController::createGroup)
 * `type='group'` qiymati bilan INSERT qiladi — bu MySQL'ga `Data truncated
 * for column 'type'` (1265) xatosini berib 500 qaytaradi.
 *
 * Shu sabab enum'ni kengaytiramiz: 'personal' | 'shop' | 'group'.
 *
 * Migration `2026_04_29_190000_add_group_fields_to_conversations_table.php`
 * title/avatar/created_by_id ustunlarini qo'shgan, lekin enum'ni unutib
 * yuborgan — shu followup faqat enum'ni tuzatadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }
        if (!Schema::hasColumn('conversations', 'type')) {
            return;
        }

        // doctrine/dbal'ga bog'liq emas — to'g'ridan-to'g'ri ALTER bilan.
        DB::statement("
            ALTER TABLE `conversations`
            MODIFY COLUMN `type` ENUM('personal','shop','group')
            NOT NULL DEFAULT 'personal'
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }

        // Avval mavjud guruh qatorlarini xavfsiz holatga keltiramiz —
        // aks holda enum'dan 'group' chiqarilgach satrlar yaroqsiz bo'ladi.
        DB::table('conversations')->where('type', 'group')->update(['type' => 'personal']);

        DB::statement("
            ALTER TABLE `conversations`
            MODIFY COLUMN `type` ENUM('personal','shop')
            NOT NULL DEFAULT 'personal'
        ");
    }
};
