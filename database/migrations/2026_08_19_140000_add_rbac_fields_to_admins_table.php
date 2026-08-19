<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Boshqaruv panelida to'liq rol/ruxsat (RBAC) tizimini yoqish uchun:
 * — is_read_only: "Auditor" turidagi adminlar faqat ko'ra oladi, hech narsani
 *   o'zgartira olmaydi (PanelPermission middleware shu maydonni tekshiradi).
 * — permissions ustuni allaqachon mavjud (JSON array), bu migratsiya uni
 *   o'zgartirmaydi — faqat yangi maydonni qo'shadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (! Schema::hasColumn('admins', 'is_read_only')) {
                $table->boolean('is_read_only')->default(false)->after('permissions');
            }
        });
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
