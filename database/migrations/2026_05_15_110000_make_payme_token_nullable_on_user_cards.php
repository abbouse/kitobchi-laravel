<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_cards') || !Schema::hasColumn('user_cards', 'payme_token')) {
            return;
        }

        $column = DB::table('information_schema.COLUMNS')
            ->select(['COLUMN_TYPE', 'IS_NULLABLE'])
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'user_cards')
            ->where('COLUMN_NAME', 'payme_token')
            ->first();

        if (!$column || ($column->IS_NULLABLE ?? null) === 'YES') {
            return;
        }

        $columnType = (string) ($column->COLUMN_TYPE ?? '');
        if ($columnType === '') {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `user_cards` MODIFY `payme_token` %s NULL',
            $columnType
        ));
    }

    public function down(): void
    {
        // No-op: legacy nullable compatibility should be preserved.
    }
};
