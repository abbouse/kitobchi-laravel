<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('project_settings', 'ai_bot_extra_notes')) {
                // Boshqaruvdan tahrir qilinadigan AI bot qo'llanmasi:
                // aksiyalar, ish vaqti, maxsus qoidalar va h.k.
                $table->text('ai_bot_extra_notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            if (Schema::hasColumn('project_settings', 'ai_bot_extra_notes')) {
                $table->dropColumn('ai_bot_extra_notes');
            }
        });
    }
};
