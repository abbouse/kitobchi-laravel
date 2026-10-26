<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kitobxon darajalari (xarid bo'yicha, 12 oylik — kelgusida imtiyozlar shunga
 * bog'lanadi) va klub nishonlari (faollik uchun, imtiyozsiz).
 * Darajalar users.position ustunida saqlanadi (avvalgidek).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loyalty_levels')) {
            Schema::create('loyalty_levels', function (Blueprint $table) {
                $table->id();
                $table->string('key', 40)->unique();
                $table->unsignedInteger('sort')->default(0);
                $table->string('name_uz', 80);
                $table->string('name_ru', 80)->nullable();
                $table->string('name_en', 80)->nullable();
                $table->string('name_ja', 80)->nullable();
                // Oxirgi 12 oyda: yetkazilgan buyurtmalar soni va mahsulotlar summasi (so'm)
                $table->unsignedInteger('min_orders')->default(0);
                $table->unsignedBigInteger('min_spent')->default(0);
                // Imtiyozlar matni (har qatorda bittasi) — ilovada ko'rsatish uchun
                $table->text('perks_uz')->nullable();
                $table->text('perks_ru')->nullable();
                $table->text('perks_en')->nullable();
                $table->text('perks_ja')->nullable();
                $table->string('color', 9)->default('#2B6FF2');
                $table->timestamps();
            });

            $now = now();
            DB::table('loyalty_levels')->insert([
                ['key' => 'reader', 'sort' => 1, 'name_uz' => 'Kitobxon', 'name_ru' => 'Читатель', 'name_en' => 'Reader', 'name_ja' => '読者',
                    'min_orders' => 0, 'min_spent' => 0, 'color' => '#8E9AAF',
                    'perks_uz' => null, 'perks_ru' => null, 'perks_en' => null, 'perks_ja' => null, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'active_reader', 'sort' => 2, 'name_uz' => 'Faol kitobxon', 'name_ru' => 'Активный читатель', 'name_en' => 'Active reader', 'name_ja' => 'アクティブ読者',
                    'min_orders' => 2, 'min_spent' => 0, 'color' => '#2B9348',
                    'perks_uz' => null, 'perks_ru' => null, 'perks_en' => null, 'perks_ja' => null, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'book_lover', 'sort' => 3, 'name_uz' => 'Kitob muxlisi', 'name_ru' => 'Любитель книг', 'name_en' => 'Book lover', 'name_ja' => '本好き',
                    'min_orders' => 5, 'min_spent' => 300000, 'color' => '#2B6FF2',
                    'perks_uz' => null, 'perks_ru' => null, 'perks_en' => null, 'perks_ja' => null, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'bibliophile', 'sort' => 4, 'name_uz' => 'Bibliofil', 'name_ru' => 'Библиофил', 'name_en' => 'Bibliophile', 'name_ja' => '愛書家',
                    'min_orders' => 12, 'min_spent' => 1000000, 'color' => '#7048E8',
                    'perks_uz' => null, 'perks_ru' => null, 'perks_en' => null, 'perks_ja' => null, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'literary_mentor', 'sort' => 5, 'name_uz' => "Adabiy yo'lboshchi", 'name_ru' => 'Литературный наставник', 'name_en' => 'Literary mentor', 'name_ja' => '文学メンター',
                    'min_orders' => 25, 'min_spent' => 3000000, 'color' => '#E8590C',
                    'perks_uz' => null, 'perks_ru' => null, 'perks_en' => null, 'perks_ja' => null, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        if (! Schema::hasTable('user_badges')) {
            Schema::create('user_badges', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('badge_key', 40);
                $table->timestamp('awarded_at');
                $table->unique(['user_id', 'badge_key']);
                $table->index(['user_id', 'awarded_at']);
            });
        }

        // Eski zinapoyadagi "klub" darajalari endi nishon — qayta hisoblanguncha boshlang'ich darajaga
        if (Schema::hasColumn('users', 'position')) {
            DB::table('users')->whereIn('position', ['reviewer', 'collector', 'book_club_star', 'market_explorer'])
                ->update(['position' => 'book_lover']);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('loyalty_levels');
    }
};
