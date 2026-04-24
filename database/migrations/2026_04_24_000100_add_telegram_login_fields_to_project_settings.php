<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('project_settings', 'telegram_login_enabled')) {
                $table->boolean('telegram_login_enabled')->default(false)->after('courier_email');
            }

            if (!Schema::hasColumn('project_settings', 'telegram_client_id')) {
                $table->string('telegram_client_id', 100)->nullable()->after('telegram_login_enabled');
            }

            if (!Schema::hasColumn('project_settings', 'telegram_redirect_uri')) {
                $table->string('telegram_redirect_uri')->nullable()->after('telegram_client_id');
            }

            if (!Schema::hasColumn('project_settings', 'telegram_scopes')) {
                $table->string('telegram_scopes', 255)->nullable()->after('telegram_redirect_uri');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            foreach ([
                'telegram_login_enabled',
                'telegram_client_id',
                'telegram_redirect_uri',
                'telegram_scopes',
            ] as $column) {
                if (Schema::hasColumn('project_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
