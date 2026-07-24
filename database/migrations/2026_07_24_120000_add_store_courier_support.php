<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Do'kon kuryeri (store courier) qo'llab-quvvatlash:
 *  - couriers.seller_id  — null = platforma kuryeri, to'la = do'kon kuryeri
 *  - couriers.service_area — kuryer yetkazish zonasi (poligon: [[lat,lon],...])
 *  - sellers.own_courier_delivery_price — do'kon kuryeri uchun flat narx (null/0 = bepul)
 *  - courier_seller_location — kuryer ↔ filial (bir nechta) many-to-many
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('couriers')) {
            Schema::table('couriers', function (Blueprint $table) {
                if (! Schema::hasColumn('couriers', 'seller_id')) {
                    $table->unsignedBigInteger('seller_id')->nullable()->after('id')->index();
                }
                if (! Schema::hasColumn('couriers', 'service_area')) {
                    // Poligon nuqtalari: [[lat, lon], ...]. Null = zona belgilanmagan.
                    $table->json('service_area')->nullable()->after('region');
                }
            });
        }

        if (Schema::hasTable('sellers') && ! Schema::hasColumn('sellers', 'own_courier_delivery_price')) {
            Schema::table('sellers', function (Blueprint $table) {
                // Do'kon kuryeri tanlansa mijoz to'laydigan flat narx. Null/0 = bepul.
                $table->unsignedInteger('own_courier_delivery_price')->nullable();
            });
        }

        if (! Schema::hasTable('courier_seller_location')) {
            Schema::create('courier_seller_location', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('courier_id')->index();
                $table->unsignedBigInteger('seller_location_id')->index();
                $table->timestamps();

                $table->unique(['courier_id', 'seller_location_id'], 'courier_branch_unique');

                if (Schema::hasTable('couriers')) {
                    $table->foreign('courier_id')->references('id')->on('couriers')->cascadeOnDelete();
                }
                if (Schema::hasTable('seller_locations')) {
                    $table->foreign('seller_location_id')->references('id')->on('seller_locations')->cascadeOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courier_seller_location')) {
            Schema::drop('courier_seller_location');
        }

        if (Schema::hasTable('sellers') && Schema::hasColumn('sellers', 'own_courier_delivery_price')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->dropColumn('own_courier_delivery_price');
            });
        }

        if (Schema::hasTable('couriers')) {
            Schema::table('couriers', function (Blueprint $table) {
                foreach (['seller_id', 'service_area'] as $column) {
                    if (Schema::hasColumn('couriers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
