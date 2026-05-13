<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hubs', function (Blueprint $table) {
            if (!Schema::hasColumn('hubs', 'priority')) {
                $table->unsignedSmallInteger('priority')->default(100)->after('is_primary');
            }
        });

        Schema::table('order_fulfillments', function (Blueprint $table) {
            if (!Schema::hasColumn('order_fulfillments', 'fulfillment_mode')) {
                $table->string('fulfillment_mode')->default('hub_based')->after('hub_id');
            }
            if (!Schema::hasColumn('order_fulfillments', 'delivery_service_id')) {
                $table->unsignedInteger('delivery_service_id')->nullable()->after('last_mile_mode');
            }
            if (!Schema::hasColumn('order_fulfillments', 'delivery_zone_rule_id')) {
                $table->foreignId('delivery_zone_rule_id')->nullable()->after('delivery_service_id')
                    ->constrained('delivery_zone_rules')->nullOnDelete();
            }
            if (!Schema::hasColumn('order_fulfillments', 'routing_version')) {
                $table->string('routing_version')->default('v1')->after('delivery_zone_rule_id');
            }
            if (!Schema::hasColumn('order_fulfillments', 'is_cod')) {
                $table->boolean('is_cod')->default(false)->after('routing_version');
            }
            if (!Schema::hasColumn('order_fulfillments', 'cash_collect_amount')) {
                $table->unsignedInteger('cash_collect_amount')->default(0)->after('is_cod');
            }
            if (!Schema::hasColumn('order_fulfillments', 'routing_snapshot')) {
                $table->json('routing_snapshot')->nullable()->after('cash_collect_amount');
            }

            $table->index(['fulfillment_mode', 'status_code'], 'order_fulfillments_mode_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_fulfillments', function (Blueprint $table) {
            if (Schema::hasColumn('order_fulfillments', 'routing_snapshot')) {
                $table->dropColumn('routing_snapshot');
            }
            if (Schema::hasColumn('order_fulfillments', 'cash_collect_amount')) {
                $table->dropColumn('cash_collect_amount');
            }
            if (Schema::hasColumn('order_fulfillments', 'is_cod')) {
                $table->dropColumn('is_cod');
            }
            if (Schema::hasColumn('order_fulfillments', 'routing_version')) {
                $table->dropColumn('routing_version');
            }
            if (Schema::hasColumn('order_fulfillments', 'delivery_zone_rule_id')) {
                $table->dropConstrainedForeignId('delivery_zone_rule_id');
            }
            if (Schema::hasColumn('order_fulfillments', 'delivery_service_id')) {
                $table->dropColumn('delivery_service_id');
            }
            if (Schema::hasColumn('order_fulfillments', 'fulfillment_mode')) {
                $table->dropColumn('fulfillment_mode');
            }
            $table->dropIndex('order_fulfillments_mode_status_idx');
        });

        Schema::table('hubs', function (Blueprint $table) {
            if (Schema::hasColumn('hubs', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
