<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Legacy `transactions.payment_type` ustuni ENUM bo'lib, 'split' qiymatini
     * qabul qilmasdi (Data truncated 1265). VARCHAR(32) ga kengaytiramiz —
     * mavjud qiymatlar saqlanadi, yangi turlar ham sig'adi.
     */
    public function up(): void
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'payment_type')) {
            return;
        }

        DB::statement("ALTER TABLE `transactions` MODIFY `payment_type` VARCHAR(32) NULL DEFAULT 'order'");

        // Truncation tufayli bo'sh qolib ketgan split tranzaksiyalarini tiklaymiz.
        DB::statement("
            UPDATE `transactions`
            SET `payment_type` = 'split'
            WHERE (`payment_type` = '' OR `payment_type` IS NULL)
              AND `provider` = 'paylov'
              AND `provider_response` LIKE '%split_contract_id%'
        ");
    }

    public function down(): void
    {
        // ENUMga qaytarmaymiz — VARCHAR barcha eski qiymatlarni qamrab oladi.
    }
};
