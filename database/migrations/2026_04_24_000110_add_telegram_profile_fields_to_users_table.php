<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'telegram_username')) {
                $table->string('telegram_username')->nullable()->after('telegram_id');
            }

            if (!Schema::hasColumn('users', 'telegram_photo')) {
                $table->text('telegram_photo')->nullable()->after('telegram_username');
            }

            if (!Schema::hasColumn('users', 'telegram_connected_at')) {
                $table->timestamp('telegram_connected_at')->nullable()->after('telegram_photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'telegram_username',
                'telegram_photo',
                'telegram_connected_at',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
