<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sellers') && ! Schema::hasColumn('sellers', 'business_role')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->string('business_role', 32)
                    ->default('seller')
                    ->after('shop_name')
                    ->index();
            });

            DB::table('sellers')
                ->whereNull('business_role')
                ->update(['business_role' => 'seller']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sellers') && Schema::hasColumn('sellers', 'business_role')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->dropIndex(['business_role']);
                $table->dropColumn('business_role');
            });
        }
    }
};
