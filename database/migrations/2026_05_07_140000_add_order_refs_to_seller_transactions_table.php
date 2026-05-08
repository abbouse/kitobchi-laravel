<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('seller_transactions', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->after('seller_id')->index();
            }
            if (!Schema::hasColumn('seller_transactions', 'seller_order_id')) {
                $table->unsignedInteger('seller_order_id')->nullable()->after('order_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('seller_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('seller_transactions', 'seller_order_id')) {
                $table->dropColumn('seller_order_id');
            }
            if (Schema::hasColumn('seller_transactions', 'order_id')) {
                $table->dropColumn('order_id');
            }
        });
    }
};
