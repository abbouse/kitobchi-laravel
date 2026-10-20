<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Boshqaruvdan naqd to'lovni (yetkazilganda to'lash) butunlay yoqish/o'chirish. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('project_settings', 'cash_on_delivery_enabled')) {
            Schema::table('project_settings', function (Blueprint $table) {
                $table->boolean('cash_on_delivery_enabled')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('project_settings', 'cash_on_delivery_enabled')) {
            Schema::table('project_settings', function (Blueprint $table) {
                $table->dropColumn('cash_on_delivery_enabled');
            });
        }
    }
};
