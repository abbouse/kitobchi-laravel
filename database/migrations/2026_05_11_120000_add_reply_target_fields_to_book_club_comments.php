<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_club_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('reply_to_user_id')->nullable()->after('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('book_club_comments', function (Blueprint $table) {
            $table->dropColumn(['reply_to_user_id']);
        });
    }
};
