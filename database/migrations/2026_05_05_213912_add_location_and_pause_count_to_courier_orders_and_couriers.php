<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('courier_orders', 'customer_delay_count')) {
                $table->unsignedTinyInteger('customer_delay_count')
                    ->default(0)
                    ->after('customer_delay_started_at');
            }
        });

        Schema::table('couriers', function (Blueprint $table) {
            if (!Schema::hasColumn('couriers', 'current_lat')) {
                $table->decimal('current_lat', 11, 8)->nullable()->after('home_address');
            }
            if (!Schema::hasColumn('couriers', 'current_lon')) {
                $table->decimal('current_lon', 11, 8)->nullable()->after('current_lat');
            }
            if (!Schema::hasColumn('couriers', 'location_updated_at')) {
                $table->timestamp('location_updated_at')->nullable()->after('current_lon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courier_orders', function (Blueprint $table) {
            if (Schema::hasColumn('courier_orders', 'customer_delay_count')) {
                $table->dropColumn('customer_delay_count');
            }
        });

        Schema::table('couriers', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('couriers', 'current_lat') ? 'current_lat' : null,
                Schema::hasColumn('couriers', 'current_lon') ? 'current_lon' : null,
                Schema::hasColumn('couriers', 'location_updated_at') ? 'location_updated_at' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
