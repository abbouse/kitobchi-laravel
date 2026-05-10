<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_zone_rules', function (Blueprint $table) {
            $table->id();
            $table->string('zone_name');
            $table->string('country_code', 8)->default('UZ');
            $table->string('scope', 20)->default('radius');
            $table->string('region_name')->nullable();
            $table->string('district_name')->nullable();
            $table->string('city_name')->nullable();
            $table->decimal('center_lat', 10, 7)->nullable();
            $table->decimal('center_lon', 10, 7)->nullable();
            $table->decimal('radius_km', 8, 2)->nullable();
            $table->foreignId('delivery_service_id')->constrained('delivery_services')->cascadeOnDelete();
            $table->integer('priority')->default(100);
            $table->integer('base_price')->nullable();
            $table->decimal('additional_seller_percent', 6, 2)->default(50);
            $table->integer('free_price_from')->nullable();
            $table->integer('eta_days')->nullable();
            $table->boolean('cod_allowed')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['country_code', 'is_active', 'priority'], 'dzr_country_active_priority_idx');
            $table->index(['delivery_service_id', 'is_active'], 'dzr_service_active_idx');
            $table->index(['scope', 'is_active'], 'dzr_scope_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zone_rules');
    }
};
