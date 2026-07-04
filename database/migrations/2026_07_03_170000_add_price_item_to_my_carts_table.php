<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('my_carts', function (Blueprint $table) {
            if (! Schema::hasColumn('my_carts', 'priceItem')) {
                $table->integer('priceItem')->nullable()->after('count_item');
            }
        });
    }

    public function down(): void
    {
        Schema::table('my_carts', function (Blueprint $table) {
            if (Schema::hasColumn('my_carts', 'priceItem')) {
                $table->dropColumn('priceItem');
            }
        });
    }
};
