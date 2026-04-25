<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Bu migration ikki ish qiladi:
     *  1) `couriers.status` enum-iga 'blocked' qiymatini qo'shadi
     *     (warn() 3-marta chaqirilganda kuryer avtomatik bloklanadi).
     *  2) `courier_ban_logs` jadvalini yaxshilaydi: nullable JSON `data` ustun
     *     hamda (courier_id, is_read) va (created_at) indekslari.
     */
    public function up(): void
    {
        // 1) couriers.status enum kengaytirish (MySQL)
        // Mavjud qiymatlar: approved, pending, rejected → + blocked
        try {
            DB::statement("ALTER TABLE couriers MODIFY COLUMN status ENUM('approved','pending','rejected','blocked') NOT NULL DEFAULT 'pending'");
        } catch (\Throwable $e) {
            // Agar default qiymat boshqa bo'lsa yoki enum mavjud bo'lmasa — log qoldiramiz, lekin
            // migration to'xtab qolmaydi.
            \Log::warning('couriers.status enum update skipped: ' . $e->getMessage());
        }

        // 2) courier_ban_logs ni yaxshilash
        if (Schema::hasTable('courier_ban_logs')) {
            Schema::table('courier_ban_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('courier_ban_logs', 'data')) {
                    $table->json('data')->nullable()->after('is_read');
                }
            });

            // Indekslarni qo'shish (ba'zi MySQL versiyalarida IF NOT EXISTS yo'q)
            $existingIndexes = collect(DB::select("SHOW INDEX FROM courier_ban_logs"))
                ->pluck('Key_name')->unique()->all();

            if (!in_array('courier_ban_logs_courier_id_is_read_index', $existingIndexes, true)) {
                Schema::table('courier_ban_logs', function (Blueprint $table) {
                    $table->index(['courier_id', 'is_read']);
                });
            }
            if (!in_array('courier_ban_logs_created_at_index', $existingIndexes, true)) {
                Schema::table('courier_ban_logs', function (Blueprint $table) {
                    $table->index('created_at');
                });
            }
        }
    }

    public function down(): void
    {
        // Indekslarni olib tashlash
        if (Schema::hasTable('courier_ban_logs')) {
            try {
                Schema::table('courier_ban_logs', function (Blueprint $table) {
                    $table->dropIndex(['courier_id', 'is_read']);
                });
            } catch (\Throwable $e) {
            }
            try {
                Schema::table('courier_ban_logs', function (Blueprint $table) {
                    $table->dropIndex(['created_at']);
                });
            } catch (\Throwable $e) {
            }
            if (Schema::hasColumn('courier_ban_logs', 'data')) {
                Schema::table('courier_ban_logs', function (Blueprint $table) {
                    $table->dropColumn('data');
                });
            }
        }

        // status enum-dan 'blocked' ni olib tashlash xavfli (mavjud bloklangan kuryerlar bor bo'lsa)
        // shuning uchun 'blocked' lar avval 'rejected' ga ko'chiriladi.
        try {
            DB::table('couriers')->where('status', 'blocked')->update(['status' => 'rejected']);
            DB::statement("ALTER TABLE couriers MODIFY COLUMN status ENUM('approved','pending','rejected') NOT NULL DEFAULT 'pending'");
        } catch (\Throwable $e) {
            \Log::warning('couriers.status enum revert skipped: ' . $e->getMessage());
        }
    }
};
