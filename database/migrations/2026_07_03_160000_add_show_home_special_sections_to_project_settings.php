<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('project_settings', 'show_home_special_sections')) {
                $table->boolean('show_home_special_sections')->default(true)->after('stop_sales');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            if (Schema::hasColumn('project_settings', 'show_home_special_sections')) {
                $table->dropColumn('show_home_special_sections');
            }
        });
    }
};
