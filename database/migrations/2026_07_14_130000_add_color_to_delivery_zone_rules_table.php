<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('delivery_zone_rules') || Schema::hasColumn('delivery_zone_rules', 'color')) {
            return;
        }

        Schema::table('delivery_zone_rules', function (Blueprint $table) {
            // Xaritada zonani ajratib ko'rsatish uchun ixtiyoriy rang (#RRGGBB).
            $table->string('color', 16)->nullable()->after('polygon');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('delivery_zone_rules') && Schema::hasColumn('delivery_zone_rules', 'color')) {
            Schema::table('delivery_zone_rules', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }
    }
};
