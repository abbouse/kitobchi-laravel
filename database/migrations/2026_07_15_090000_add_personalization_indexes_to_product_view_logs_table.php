<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_view_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'pvl_user_created_idx');
            $table->index(['session_id', 'created_at'], 'pvl_session_created_idx');
            $table->index(['device_id', 'created_at'], 'pvl_device_created_idx');
            $table->index(['user_id', 'product_type', 'product_id', 'created_at'], 'pvl_user_product_recent_idx');
            $table->index(['session_id', 'product_type', 'product_id', 'created_at'], 'pvl_session_product_recent_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_view_logs', function (Blueprint $table) {
            $table->dropIndex('pvl_user_created_idx');
            $table->dropIndex('pvl_session_created_idx');
            $table->dropIndex('pvl_device_created_idx');
            $table->dropIndex('pvl_user_product_recent_idx');
            $table->dropIndex('pvl_session_product_recent_idx');
        });
    }
};
