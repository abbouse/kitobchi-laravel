<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('delivery_zone_rules')) {
            return;
        }

        if (Schema::hasColumn('delivery_zone_rules', 'polygon')) {
            return;
        }

        Schema::table('delivery_zone_rules', function (Blueprint $table) {
            // GeoJSON-ga o'xshash [[lat, lon], ...] nuqtalar ro'yxati — polygon scope uchun.
            // Bounding box (min/max) tez filtrlash uchun oldindan hisoblab saqlanadi.
            $table->json('polygon')->nullable()->after('radius_km');
            $table->decimal('bbox_min_lat', 10, 7)->nullable()->after('polygon');
            $table->decimal('bbox_min_lon', 10, 7)->nullable()->after('bbox_min_lat');
            $table->decimal('bbox_max_lat', 10, 7)->nullable()->after('bbox_min_lon');
            $table->decimal('bbox_max_lon', 10, 7)->nullable()->after('bbox_max_lat');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('delivery_zone_rules')) {
            return;
        }

        Schema::table('delivery_zone_rules', function (Blueprint $table) {
            foreach (['polygon', 'bbox_min_lat', 'bbox_min_lon', 'bbox_max_lat', 'bbox_max_lon'] as $column) {
                if (Schema::hasColumn('delivery_zone_rules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
