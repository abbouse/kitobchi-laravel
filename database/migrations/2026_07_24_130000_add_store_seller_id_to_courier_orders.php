<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * courier_orders.store_seller_id — do'kon kuryeri buyurtmasi bo'lsa shu do'kon id.
 * null = oddiy (platforma) buyurtma. Feed filtri shu bo'yicha ajratadi:
 *  - platforma kuryeri: faqat store_seller_id IS NULL;
 *  - do'kon kuryeri: faqat store_seller_id = o'z do'koni.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courier_orders') && ! Schema::hasColumn('courier_orders', 'store_seller_id')) {
            Schema::table('courier_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('store_seller_id')->nullable()->after('order_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courier_orders') && Schema::hasColumn('courier_orders', 'store_seller_id')) {
            Schema::table('courier_orders', function (Blueprint $table) {
                $table->dropColumn('store_seller_id');
            });
        }
    }
};
