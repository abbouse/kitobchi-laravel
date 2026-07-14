<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fcm_notifications', function (Blueprint $table) {
            foreach (['uz', 'ru', 'en', 'ja'] as $locale) {
                if (! Schema::hasColumn('fcm_notifications', "name_{$locale}")) {
                    $table->string("name_{$locale}", 255)->nullable()->after('description');
                }
                if (! Schema::hasColumn('fcm_notifications', "description_{$locale}")) {
                    $table->text("description_{$locale}")->nullable()->after("name_{$locale}");
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('fcm_notifications', function (Blueprint $table) {
            $columns = [];
            foreach (['uz', 'ru', 'en', 'ja'] as $locale) {
                if (Schema::hasColumn('fcm_notifications', "name_{$locale}")) {
                    $columns[] = "name_{$locale}";
                }
                if (Schema::hasColumn('fcm_notifications', "description_{$locale}")) {
                    $columns[] = "description_{$locale}";
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
