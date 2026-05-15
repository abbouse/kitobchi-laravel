<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_cards') || Schema::hasColumn('user_cards', 'card_fingerprint')) {
            return;
        }

        Schema::table('user_cards', function (Blueprint $table) {
            $table->string('card_fingerprint', 64)
                ->nullable()
                ->unique()
                ->after('card_number');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_cards') || !Schema::hasColumn('user_cards', 'card_fingerprint')) {
            return;
        }

        Schema::table('user_cards', function (Blueprint $table) {
            $table->dropUnique('user_cards_card_fingerprint_unique');
            $table->dropColumn('card_fingerprint');
        });
    }
};
