<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->unsignedInteger('rating_reviews_count')
                ->default(0)
                ->after('rating');

            $table->decimal('reputation_score', 5, 2)
                ->default(78.00)
                ->after('rating_reviews_count');

            $table->timestamp('reputation_last_calculated_at')
                ->nullable()
                ->after('reputation_score');
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropColumn([
                'rating_reviews_count',
                'reputation_score',
                'reputation_last_calculated_at',
            ]);
        });
    }
};
