<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('project_settings', 'telegram_redirect_uri_ios')) {
                $table->string('telegram_redirect_uri_ios')->nullable()->after('telegram_redirect_uri');
            }

            if (!Schema::hasColumn('project_settings', 'telegram_redirect_uri_android')) {
                $table->string('telegram_redirect_uri_android')->nullable()->after('telegram_redirect_uri_ios');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            foreach ([
                'telegram_redirect_uri_ios',
                'telegram_redirect_uri_android',
            ] as $column) {
                if (Schema::hasColumn('project_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
