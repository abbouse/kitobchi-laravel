<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curated_collections', function (Blueprint $table) {
            $table->unsignedInteger('custom_total_price')->nullable()->after('button_text_color');
        });
    }

    public function down(): void
    {
        Schema::table('curated_collections', function (Blueprint $table) {
            $table->dropColumn('custom_total_price');
        });
    }
};
