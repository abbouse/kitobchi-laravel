<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_order_items', function (Blueprint $table) {
            $hasRefundedAt = Schema::hasColumn('seller_order_items', 'refunded_at');
            if (! Schema::hasColumn('seller_order_items', 'cancel_requested_at')) {
                $column = $table->timestamp('cancel_requested_at')->nullable();
                if ($hasRefundedAt) {
                    $column->after('refunded_at');
                }
            }
            if (! Schema::hasColumn('seller_order_items', 'cancel_restore_until')) {
                $table->timestamp('cancel_restore_until')->nullable()->after('cancel_requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seller_order_items', function (Blueprint $table) {
            foreach (['cancel_restore_until', 'cancel_requested_at'] as $column) {
                if (Schema::hasColumn('seller_order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
