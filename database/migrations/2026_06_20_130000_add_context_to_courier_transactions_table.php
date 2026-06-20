<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courier_transactions')) {
            return;
        }

        Schema::table('courier_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('courier_transactions', 'type')) {
                $table->string('type', 20)->default('expense')->after('card');
            }
            if (! Schema::hasColumn('courier_transactions', 'category')) {
                $table->string('category', 60)->default('withdrawal')->after('type');
            }
            if (! Schema::hasColumn('courier_transactions', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->after('category');
            }
            if (! Schema::hasColumn('courier_transactions', 'courier_order_id')) {
                $table->unsignedBigInteger('courier_order_id')->nullable()->after('order_id');
            }
            if (! Schema::hasColumn('courier_transactions', 'courier_task_id')) {
                $table->unsignedBigInteger('courier_task_id')->nullable()->after('courier_order_id');
            }
            if (! Schema::hasColumn('courier_transactions', 'description')) {
                $table->string('description')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('courier_transactions')) {
            return;
        }

        Schema::table('courier_transactions', function (Blueprint $table) {
            foreach (['description', 'courier_task_id', 'courier_order_id', 'order_id', 'category', 'type'] as $column) {
                if (Schema::hasColumn('courier_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
