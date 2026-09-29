<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy `status` enum ustunlariga kod ishlatadigan barcha qiymatlar:
 *   courier_orders.status — 'customer_received', 'returned'
 *   solds.status          — 'D' (mijoz qabul qildi)
 * Strict rejimda bu holatlar saqlanmay qolardi. Bazada allaqachon bo'lsa,
 * hech narsa qilinmaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->extend('courier_orders', ['pay_process', 'pending', 'in_delivery', 'delivered', 'customer_received', 'rejected', 'returned']);
        $this->extend('solds', ['A', 'P', 'B', 'C', 'D', 'F']);
    }

    private function extend(string $table, array $required): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $column = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', 'status')
            ->first(['COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT']);
        $type = (string) ($column->COLUMN_TYPE ?? '');

        if (! str_starts_with($type, 'enum(')) {
            return;
        }

        $missing = array_filter($required, fn ($v) => ! str_contains($type, "'{$v}'"));
        if ($missing === []) {
            return;
        }

        // Mavjud qiymatlar va ularning tartibi saqlanadi, yetishmaganlari qo'shiladi
        preg_match_all("/'([^']*)'/", $type, $m);
        $values = array_values(array_unique(array_merge($m[1], $required)));
        $pdo = DB::getPdo();
        $list = implode(',', array_map(fn ($v) => $pdo->quote($v), $values));
        $null = ($column->IS_NULLABLE ?? 'YES') === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column->COLUMN_DEFAULT !== null ? ' DEFAULT '.$pdo->quote((string) $column->COLUMN_DEFAULT) : '';

        DB::statement("ALTER TABLE `{$table}` MODIFY `status` ENUM({$list}) {$null}{$default}");
    }

    public function down(): void
    {
        //
    }
};
