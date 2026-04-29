<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_role', 30)->nullable()->after('position');
        });

        DB::table('users')
            ->whereIn(DB::raw('LOWER(TRIM(position))'), ['moderator', 'administrator', 'admin'])
            ->update([
                'staff_role' => DB::raw("
                    CASE
                        WHEN LOWER(TRIM(position)) = 'moderator' THEN 'moderator'
                        ELSE 'administrator'
                    END
                "),
            ]);

        DB::table('users')
            ->whereIn(DB::raw('LOWER(TRIM(position))'), ['moderator', 'administrator', 'admin'])
            ->update(['position' => null]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('staff_role');
        });
    }
};
