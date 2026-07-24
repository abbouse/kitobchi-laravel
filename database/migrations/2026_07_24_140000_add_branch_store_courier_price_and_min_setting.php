<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seller_locations')) {
            Schema::table('seller_locations', function (Blueprint $table) {
                if (! Schema::hasColumn('seller_locations', 'store_courier_delivery_price')) {
                    $table->unsignedInteger('store_courier_delivery_price')->nullable()->after('is_deleted');
                }
            });

            if (Schema::hasTable('sellers') && Schema::hasColumn('sellers', 'own_courier_delivery_price')) {
                DB::statement("
                    UPDATE seller_locations sl
                    JOIN sellers s ON s.id = sl.seller_id
                    SET sl.store_courier_delivery_price = s.own_courier_delivery_price
                    WHERE sl.store_courier_delivery_price IS NULL
                      AND s.own_courier_delivery_price IS NOT NULL
                ");
            }
        }

        if (Schema::hasTable('couriers')) {
            Schema::table('couriers', function (Blueprint $table) {
                if (! Schema::hasColumn('couriers', 'store_courier_hidden_at')) {
                    $table->timestamp('store_courier_hidden_at')->nullable()->after('service_area')->index();
                }
            });
        }

        if (Schema::hasTable('project_settings')) {
            Schema::table('project_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('project_settings', 'seller_courier_min_delivery_price')) {
                    $table->unsignedInteger('seller_courier_min_delivery_price')->default(0)->after('courier_min_fee');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_settings') && Schema::hasColumn('project_settings', 'seller_courier_min_delivery_price')) {
            Schema::table('project_settings', function (Blueprint $table) {
                $table->dropColumn('seller_courier_min_delivery_price');
            });
        }

        if (Schema::hasTable('couriers') && Schema::hasColumn('couriers', 'store_courier_hidden_at')) {
            Schema::table('couriers', function (Blueprint $table) {
                $table->dropColumn('store_courier_hidden_at');
            });
        }

        if (Schema::hasTable('seller_locations') && Schema::hasColumn('seller_locations', 'store_courier_delivery_price')) {
            Schema::table('seller_locations', function (Blueprint $table) {
                $table->dropColumn('store_courier_delivery_price');
            });
        }
    }
};
