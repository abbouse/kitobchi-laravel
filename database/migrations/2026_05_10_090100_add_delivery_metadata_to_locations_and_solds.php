<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('country_code', 8)->nullable()->after('fullAddress');
            $table->string('region_slug')->nullable()->after('country_code');
            $table->string('region_name')->nullable()->after('region_slug');
            $table->string('district_name')->nullable()->after('region_name');
            $table->string('city_name')->nullable()->after('district_name');
        });

        Schema::table('solds', function (Blueprint $table) {
            $table->foreignId('delivery_zone_rule_id')->nullable()->after('deliveryPrice')->constrained('delivery_zone_rules')->nullOnDelete();
            $table->json('delivery_rule_snapshot')->nullable()->after('delivery_zone_rule_id');
        });
    }

    public function down(): void
    {
        Schema::table('solds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_zone_rule_id');
            $table->dropColumn('delivery_rule_snapshot');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'country_code',
                'region_slug',
                'region_name',
                'district_name',
                'city_name',
            ]);
        });
    }
};
