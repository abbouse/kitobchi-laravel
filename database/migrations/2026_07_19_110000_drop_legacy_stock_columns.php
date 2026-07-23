<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LEGACY STOCK USTUNLARINI O'CHIRISH.
 *
 * DIQQAT: bu migratsiya faqat create_branch_stocks_tables (backfill) dan
 * KEYIN ishlaydi. Stock endi yagona manbada — branch_stocks.
 * API javoblaridagi `count`/`stock` maydonlari model accessorlari orqali
 * (barcha filiallar yig'indisi) qaytarilaveradi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('books', 'count')) {
            Schema::table('books', fn (Blueprint $t) => $t->dropColumn('count'));
        }
        if (Schema::hasColumn('stationeries', 'stock')) {
            Schema::table('stationeries', fn (Blueprint $t) => $t->dropColumn('stock'));
        }
        if (Schema::hasColumn('stationery_variants', 'stock')) {
            Schema::table('stationery_variants', fn (Blueprint $t) => $t->dropColumn('stock'));
        }
        if (Schema::hasColumn('gifts', 'stock')) {
            Schema::table('gifts', fn (Blueprint $t) => $t->dropColumn('stock'));
        }
    }

    public function down(): void
    {
        // Ustunlar qayta yaratiladi va branch_stocks yig'indisidan tiklanadi.
        if (! Schema::hasColumn('books', 'count')) {
            Schema::table('books', fn (Blueprint $t) => $t->unsignedInteger('count')->default(0));
            $this->restore('book', 'books', 'count');
        }
        if (! Schema::hasColumn('stationeries', 'stock')) {
            Schema::table('stationeries', fn (Blueprint $t) => $t->unsignedInteger('stock')->default(0));
            $this->restore('stationery', 'stationeries', 'stock', productLevelOnly: true);
        }
        if (! Schema::hasColumn('stationery_variants', 'stock')) {
            Schema::table('stationery_variants', fn (Blueprint $t) => $t->unsignedInteger('stock')->default(0));
            \Illuminate\Support\Facades\DB::statement("
                UPDATE stationery_variants v SET stock = (
                    SELECT COALESCE(SUM(bs.quantity - bs.reserved), 0) FROM branch_stocks bs
                    WHERE bs.product_type = 'stationery' AND bs.product_id = v.product_id AND bs.variant_id = v.id
                )
            ");
        }
        if (! Schema::hasColumn('gifts', 'stock')) {
            Schema::table('gifts', fn (Blueprint $t) => $t->unsignedInteger('stock')->default(0));
            $this->restore('gift', 'gifts', 'stock');
        }
    }

    private function restore(string $type, string $table, string $column, bool $productLevelOnly = false): void
    {
        $variantCond = $productLevelOnly ? 'AND bs.variant_id = 0' : '';
        \Illuminate\Support\Facades\DB::statement("
            UPDATE {$table} t SET {$column} = (
                SELECT COALESCE(SUM(bs.quantity - bs.reserved), 0) FROM branch_stocks bs
                WHERE bs.product_type = '{$type}' AND bs.product_id = t.id {$variantCond}
            )
        ");
    }
};
