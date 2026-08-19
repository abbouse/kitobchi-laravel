<?php

use App\Models\Admin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Boshqaruv panelida to'liq rol/ruxsat (RBAC) tizimini yoqish uchun:
 * — is_read_only: "Auditor" turidagi adminlar faqat ko'ra oladi, hech narsani
 *   o'zgartira olmaydi (PanelPermission middleware shu maydonni tekshiradi).
 * — permissions ustuni allaqachon mavjud (JSON array), bu migratsiya uni
 *   o'zgartirmaydi — faqat yangi maydonni qo'shadi.
 *
 * MUHIM (backfill): shu migratsiyagacha panelda granular ruxsat tekshiruvi
 * umuman yo'q edi — har qanday autentifikatsiya qilingan admin (superadmin
 * bo'lmasa ham) barcha bo'limlarga kira olardi. Route'larga panel.permission
 * middleware ulanganidan keyin, agar mavjud adminlarning permissions ustuni
 * bo'sh/NULL bo'lib qolsa, ular birdaniga hech narsani ko'ra olmay qoladi
 * (butun panel 403 bilan yopiladi). Buning oldini olish uchun — faqat shu
 * migratsiya birinchi marta ishga tushganda — permissions bo'sh bo'lgan,
 * superadmin bo'lmagan barcha mavjud adminlarga barcha modullarga ruxsat
 * beramiz; superadmin keyin Adminlar sahifasida buni xohlagancha
 * qattiqlashtirishi mumkin. Yangi (bu migratsiyadan keyin) yaratiladigan
 * adminlar esa odatdagidek tanlangan rol shabloniga ko'ra ruxsat oladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        $columnAlreadyExisted = Schema::hasColumn('admins', 'is_read_only');

        Schema::table('admins', function (Blueprint $table) {
            if (! Schema::hasColumn('admins', 'is_read_only')) {
                $table->boolean('is_read_only')->default(false)->after('permissions');
            }
        });

        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'role')) {
            DB::statement("ALTER TABLE `admins` MODIFY COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'admin'");
        }

        if (! $columnAlreadyExisted && Schema::hasTable('admins')) {
            $allModules = json_encode(array_keys(Admin::MODULES));

            DB::table('admins')
                ->where('role', '!=', 'superadmin')
                ->where(function ($query) {
                    $query->whereNull('permissions')
                        ->orWhere('permissions', '[]')
                        ->orWhere('permissions', '');
                })
                ->update(['permissions' => $allModules]);
        }
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'is_read_only')) {
                $table->dropColumn('is_read_only');
            }
        });
    }
};
