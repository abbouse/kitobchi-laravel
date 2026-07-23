<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Speeds up the hub app list endpoints (queue / exceptions / scan) and the
 * dashboard counts. These queries all filter by `hub_id` (+ `status_code`)
 * and order by `updated_at DESC`; without a matching composite index MySQL
 * has to filesort every page. Additive change — safe to run online.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_fulfillments', function (Blueprint $table) {
            // Queue lists: WHERE hub_id = ? AND status_code IN (...) ORDER BY updated_at DESC
            $table->index(['hub_id', 'status_code', 'updated_at'], 'of_hub_status_updated_idx');
            // Exceptions / activity: WHERE hub_id = ? ORDER BY updated_at DESC
            $table->index(['hub_id', 'updated_at'], 'of_hub_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_fulfillments', function (Blueprint $table) {
            $table->dropIndex('of_hub_status_updated_idx');
            $table->dropIndex('of_hub_updated_idx');
        });
    }
};
