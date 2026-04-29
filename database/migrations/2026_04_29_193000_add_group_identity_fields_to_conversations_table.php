<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'public_username')) {
                $table->string('public_username')->nullable()->unique()->after('is_public');
            }

            if (!Schema::hasColumn('conversations', 'invite_token')) {
                $table->string('invite_token')->nullable()->unique()->after('public_username');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'invite_token')) {
                $table->dropUnique(['invite_token']);
                $table->dropColumn('invite_token');
            }

            if (Schema::hasColumn('conversations', 'public_username')) {
                $table->dropUnique(['public_username']);
                $table->dropColumn('public_username');
            }
        });
    }
};
