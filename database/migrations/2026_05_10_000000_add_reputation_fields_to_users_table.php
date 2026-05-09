<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('reputation_score', 5, 2)
                ->default(82.00)
                ->after('cashback');

            $table->boolean('cash_on_delivery_allowed')
                ->default(true)
                ->after('reputation_score');

            $table->unsignedInteger('cod_return_strikes')
                ->default(0)
                ->after('cash_on_delivery_allowed');

            $table->timestamp('reputation_last_calculated_at')
                ->nullable()
                ->after('cod_return_strikes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'reputation_score',
                'cash_on_delivery_allowed',
                'cod_return_strikes',
                'reputation_last_calculated_at',
            ]);
        });
    }
};
