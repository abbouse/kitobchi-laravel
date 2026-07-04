<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solds', function (Blueprint $table) {
            if (! Schema::hasColumn('solds', 'collectionDiscountAmount')) {
                $table->unsignedInteger('collectionDiscountAmount')
                    ->default(0)
                    ->after('discountAmount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('solds', function (Blueprint $table) {
            if (Schema::hasColumn('solds', 'collectionDiscountAmount')) {
                $table->dropColumn('collectionDiscountAmount');
            }
        });
    }
};
