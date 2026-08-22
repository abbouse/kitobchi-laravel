<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Web uchun manzil endi GPS/geolocation orqali emas, balki Viloyat →
// Tuman → Mahalla/qishloq tanlovi orqali kiritiladi (`region_name`,
// `district_name`, `city_name` ustunlari) — shu sababli `lat`/`lon`
// endi web oqimi uchun MAJBURIY emas. Ustunlar hali ham mavjud (mobil
// ilova/kuryer oqimlari GPS koordinatasini yuborishda davom etadi),
// faqat NOT NULL cheklovi olib tashlanadi.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `locations` MODIFY `lat` VARCHAR(20) NULL");
        DB::statement("ALTER TABLE `locations` MODIFY `lon` VARCHAR(20) NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE `locations` SET `lat` = '0' WHERE `lat` IS NULL");
        DB::statement("UPDATE `locations` SET `lon` = '0' WHERE `lon` IS NULL");
        DB::statement("ALTER TABLE `locations` MODIFY `lat` VARCHAR(20) NOT NULL");
        DB::statement("ALTER TABLE `locations` MODIFY `lon` VARCHAR(20) NOT NULL");
    }
};
