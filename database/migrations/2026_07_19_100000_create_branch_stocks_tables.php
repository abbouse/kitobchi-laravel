<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FILIAL-DARAJALI INVENTAR — yadro jadvallar.
 *
 * branch_stocks   — har (filial × mahsulot × variant) uchun bitta qator.
 *                   variant_id = 0 → mahsulot darajasi (variantsiz).
 * stock_movements — append-only ledger: har bir stock o'zgarishi kim/nega.
 *
 * Backfill: mavjud books.count / stationery.stock / stationery_variants.stock /
 * gifts.stock qiymatlari sellerning ASOSIY filialiga ko'chiriladi. Ustunlarning
 * o'zi alohida migratsiyada (drop_legacy_stock_columns) o'chiriladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('seller_location_id');
            $table->string('product_type', 20); // book | stationery | gift
            $table->unsignedBigInteger('product_id');
            // 0 = mahsulot darajasi. NULL emas — MySQL unique indeksda NULLlar
            // takrorlanishiga yo'l qo'yadi, 0 esa qat'iy unique kafolat beradi.
            $table->unsignedBigInteger('variant_id')->default(0);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved')->default(0);
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->timestamps();

            $table->unique(
                ['seller_location_id', 'product_type', 'product_id', 'variant_id'],
                'branch_stocks_loc_product_unique'
            );
            $table->index(['product_type', 'product_id', 'variant_id'], 'branch_stocks_product_idx');
            $table->index(['seller_id', 'product_type'], 'branch_stocks_seller_idx');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_stock_id')->index();
            $table->integer('delta');
            $table->unsignedInteger('quantity_after');
            $table->string('reason', 32); // sale | cancel_return | manual_adjust | intake | transfer_out | transfer_in | inventory_count | migration
            $table->string('actor_type', 20)->nullable(); // seller | staff | admin | system | api_client
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('ref_type', 40)->nullable(); // seller_order_item, sold, ...
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['ref_type', 'ref_id'], 'stock_movements_ref_idx');
        });

        $this->backfill();
    }

    private function backfill(): void
    {
        // Har seller uchun asosiy (main → eng kichik id) faol filial.
        $mainLocationJoin = '
            JOIN seller_locations loc ON loc.seller_id = %s.seller_id
                AND loc.is_deleted = 0
                AND loc.id = (
                    SELECT sl2.id FROM seller_locations sl2
                    WHERE sl2.seller_id = loc.seller_id AND sl2.is_deleted = 0
                    ORDER BY sl2.is_main DESC, sl2.id ASC LIMIT 1
                )';

        $now = now()->toDateTimeString();

        // Kitoblar
        DB::statement("
            INSERT INTO branch_stocks (seller_id, seller_location_id, product_type, product_id, variant_id, quantity, reserved, created_at, updated_at)
            SELECT b.seller_id, loc.id, 'book', b.id, 0, GREATEST(COALESCE(b.count, 0), 0), 0, '{$now}', '{$now}'
            FROM books b " . sprintf($mainLocationJoin, 'b') . '
            WHERE COALESCE(b.count, 0) > 0
        ');

        // Kanstovar (mahsulot darajasi)
        DB::statement("
            INSERT INTO branch_stocks (seller_id, seller_location_id, product_type, product_id, variant_id, quantity, reserved, created_at, updated_at)
            SELECT s.seller_id, loc.id, 'stationery', s.id, 0, GREATEST(COALESCE(s.stock, 0), 0), 0, '{$now}', '{$now}'
            FROM stationeries s " . sprintf($mainLocationJoin, 's') . '
            WHERE COALESCE(s.stock, 0) > 0
        ');

        // Kanstovar variantlari
        DB::statement("
            INSERT INTO branch_stocks (seller_id, seller_location_id, product_type, product_id, variant_id, quantity, reserved, created_at, updated_at)
            SELECT s.seller_id, loc.id, 'stationery', s.id, v.id, GREATEST(COALESCE(v.stock, 0), 0), 0, '{$now}', '{$now}'
            FROM stationery_variants v
            JOIN stationeries s ON s.id = v.product_id " . sprintf($mainLocationJoin, 's') . '
            WHERE COALESCE(v.stock, 0) > 0
        ');

        // Sovg'alar
        DB::statement("
            INSERT INTO branch_stocks (seller_id, seller_location_id, product_type, product_id, variant_id, quantity, reserved, created_at, updated_at)
            SELECT g.seller_id, loc.id, 'gift', g.id, 0, GREATEST(COALESCE(g.stock, 0), 0), 0, '{$now}', '{$now}'
            FROM gifts g " . sprintf($mainLocationJoin, 'g') . '
            WHERE COALESCE(g.stock, 0) > 0
        ');

        // Ledger: migratsiya yozuvi
        DB::statement("
            INSERT INTO stock_movements (branch_stock_id, delta, quantity_after, reason, actor_type, note, created_at)
            SELECT id, quantity, quantity, 'migration', 'system', 'Legacy ustundan ko\\'chirildi', '{$now}'
            FROM branch_stocks
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('branch_stocks');
    }
};
