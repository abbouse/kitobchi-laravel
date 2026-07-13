<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropLegacyColumns('books', [
            'kangaroo_listing_decision',
            'kangaroo_listing_score',
            'kangaroo_listing_checked_at',
            'kangaroo_listing_issues',
        ]);
        $this->dropLegacyColumns('stationeries', [
            'kangaroo_listing_decision',
            'kangaroo_listing_score',
            'kangaroo_listing_checked_at',
            'kangaroo_listing_issues',
        ]);

        $this->dropIndexIfExists('book_club_comments', 'bcc_k_ugc_status_idx');
        $this->dropLegacyColumns('book_club_comments', [
            'kangaroo_star_equivalent',
            'kangaroo_toxicity',
            'kangaroo_checked_at',
            'kangaroo_ugc_status',
        ]);

        $this->dropIndexIfExists('book_club', 'bc_post_ugc_status_idx');
        $this->dropLegacyColumns('book_club', [
            'kangaroo_post_star',
            'kangaroo_post_checked_at',
            'kangaroo_post_ugc_status',
        ]);
    }

    public function down(): void
    {
        // Retired servis ustunlari ataylab qayta yaratilmaydi.
    }

    /** @param list<string> $columns */
    private function dropLegacyColumns(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $existing = array_values(array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn($tableName, $column),
        ));

        if ($existing !== []) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (Schema::hasTable($tableName) && Schema::hasIndex($tableName, $indexName)) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropIndex($indexName));
        }
    }
};
