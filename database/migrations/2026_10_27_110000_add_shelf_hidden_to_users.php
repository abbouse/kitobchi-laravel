<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kitobxonlar klubi profilidagi "Javon": foydalanuvchi uni boshqalardan yashira oladi. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'shelf_hidden')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('shelf_hidden')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'shelf_hidden')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('shelf_hidden');
            });
        }
    }
};
