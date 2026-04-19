<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_club_comments', function (Blueprint $table) {
            $table->string('kangaroo_ugc_status', 32)->nullable()->after('kangaroo_checked_at');
            $table->index('kangaroo_ugc_status', 'bcc_k_ugc_status_idx');
        });

        Schema::table('book_club', function (Blueprint $table) {
            $table->string('kangaroo_post_ugc_status', 32)->nullable()->after('kangaroo_post_checked_at');
            $table->index('kangaroo_post_ugc_status', 'bc_post_ugc_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('book_club_comments', function (Blueprint $table) {
            $table->dropIndex('bcc_k_ugc_status_idx');
            $table->dropColumn('kangaroo_ugc_status');
        });

        Schema::table('book_club', function (Blueprint $table) {
            $table->dropIndex('bc_post_ugc_status_idx');
            $table->dropColumn('kangaroo_post_ugc_status');
        });
    }
};
