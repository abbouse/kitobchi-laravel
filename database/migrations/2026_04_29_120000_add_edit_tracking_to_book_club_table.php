<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_club', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('updated_at');
            $table->unsignedInteger('edit_count')->default(0)->after('edited_at');
            $table->unsignedBigInteger('last_edited_by_id')->nullable()->after('edit_count');
            $table->index('last_edited_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('book_club', function (Blueprint $table) {
            $table->dropIndex(['last_edited_by_id']);
            $table->dropColumn(['edited_at', 'edit_count', 'last_edited_by_id']);
        });
    }
};
