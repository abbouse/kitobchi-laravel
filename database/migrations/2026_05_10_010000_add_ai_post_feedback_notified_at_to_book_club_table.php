<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_club', function (Blueprint $table) {
            $table->timestamp('ai_post_feedback_notified_at')
                ->nullable()
                ->after('ai_post_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('book_club', function (Blueprint $table) {
            $table->dropColumn('ai_post_feedback_notified_at');
        });
    }
};
