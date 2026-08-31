<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// MUHIM: boshqaruv (admin) panelida buyurtma veb-saytdan yoki Kitobchi
// ilovasidan tushganini ajratish uchun. `solds.order_source` — nullable,
// default 'app' (mavjud/eski buyurtmalar va mobil ilova hech narsa
// yubormaydi, shu sabab 'app' xavfsiz standart hisoblanadi). Web frontend
// `X-Client-Platform: web` headerini yuborganda 'web' bo'lib yoziladi —
// app/Http/Controllers/Api/PurchaseController.php::resolveOrderSource().
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solds', function (Blueprint $table) {
            $table->string('order_source', 10)->nullable()->default('app')->after('is_instore');
        });
    }

    public function down(): void
    {
        Schema::table('solds', function (Blueprint $table) {
            $table->dropColumn('order_source');
        });
    }
};
