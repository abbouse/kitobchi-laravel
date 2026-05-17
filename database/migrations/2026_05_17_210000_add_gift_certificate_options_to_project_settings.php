<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('project_settings', 'gift_certificate_options')) {
                $table->json('gift_certificate_options')->nullable()->after('packaging_threshold');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            if (Schema::hasColumn('project_settings', 'gift_certificate_options')) {
                $table->dropColumn('gift_certificate_options');
            }
        });
    }
};
