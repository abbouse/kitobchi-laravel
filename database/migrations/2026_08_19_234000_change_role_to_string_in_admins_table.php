<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable("admins") && Schema::hasColumn("admins", "role")) {
            DB::statement("ALTER TABLE `admins` MODIFY COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'admin'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable("admins") && Schema::hasColumn("admins", "role")) {
            DB::statement("ALTER TABLE `admins` MODIFY COLUMN `role` ENUM('superadmin','admin','moderator') NOT NULL DEFAULT 'moderator'");
        }
    }
};
