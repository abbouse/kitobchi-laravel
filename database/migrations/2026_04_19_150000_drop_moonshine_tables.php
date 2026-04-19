<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moonshine admin paketi olib tashlangach — qolgan jadvallarni bazadan chiqarish.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('moonshine_socialites');
        Schema::dropIfExists('moonshine_users');
        Schema::dropIfExists('moonshine_user_roles');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        //
    }
};
