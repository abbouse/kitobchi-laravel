<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_fulfillments')
            || Schema::hasColumn('order_fulfillments', 'postal_provider')) {
            return;
        }

        Schema::table('order_fulfillments', function (Blueprint $table) {
            $table->string('postal_provider', 32)
                ->nullable()
                ->after('postal_tracking_number')
                ->index();
        });

        DB::table('order_fulfillments')
            ->whereNotNull('postal_tracking_number')
            ->where('postal_tracking_number', '!=', '')
            ->whereNull('postal_provider')
            ->update(['postal_provider' => 'uzpost']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_fulfillments')
            || ! Schema::hasColumn('order_fulfillments', 'postal_provider')) {
            return;
        }

        Schema::table('order_fulfillments', function (Blueprint $table) {
            $table->dropColumn('postal_provider');
        });
    }
};
