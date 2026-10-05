<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Sovg'alar g'ildiragi" o'yini: tangalar hamyoni, sovg'alar (qiyinlik va
 * tushish foizi bilan), aylantirishlar, kitob bo'laklari, yutuqlar
 * (shaxsiy 1 oylik promokodlar), vazifalar va sozlamalar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('game_wallets')) {
            Schema::create('game_wallets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->integer('coins')->default(0);
                $table->unsignedInteger('total_earned')->default(0);
                $table->unsignedInteger('total_spent')->default(0);
                $table->timestamp('last_daily_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('game_coin_transactions')) {
            Schema::create('game_coin_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->integer('amount');
                $table->string('reason', 20); // daily | order | task | spin | prize | admin
                $table->string('ref', 60)->nullable(); // takrorlanmaslik kaliti
                $table->string('note')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['user_id', 'created_at']);
                $table->unique(['user_id', 'reason', 'ref']);
            });
        }

        if (! Schema::hasTable('game_prizes')) {
            Schema::create('game_prizes', function (Blueprint $table) {
                $table->id();
                // fragments — kitob bo'laklari, promocode — chegirma, coins — tanga
                $table->string('type', 20);
                $table->string('title_uz');
                $table->string('title_ru')->nullable();
                $table->string('image')->nullable();
                // easy | medium | hard — boshqaruvdagi tayyor foizlar uchun
                $table->string('difficulty', 10)->default('medium');
                // Har bir aylantirishda tushish ehtimoli, %
                $table->decimal('chance', 6, 3)->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('position')->default(0);

                // Kitob bo'laklari: nechta bo'lak va qaysi kitob (global katalog)
                $table->unsignedTinyInteger('fragments_total')->nullable();
                $table->unsignedBigInteger('edition_id')->nullable();

                // Tanga
                $table->unsignedInteger('coins_amount')->nullable();

                // Promokod: percent | fixed; qamrovi: all | edition | seller
                $table->string('discount_type', 10)->nullable();
                $table->unsignedInteger('discount_value')->nullable();
                $table->unsignedInteger('max_discount')->nullable();
                $table->unsignedInteger('min_order_amount')->nullable();
                $table->string('scope', 10)->default('all');
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->unsignedSmallInteger('valid_days')->default(30);

                // Yutish shartlari
                $table->unsignedInteger('min_orders')->default(0); // yetkazilgan buyurtmalar soni
                $table->unsignedInteger('min_spins')->default(0); // shu vaqtgacha aylantirishlar
                $table->unsignedInteger('max_wins_per_user')->nullable();
                $table->unsignedInteger('daily_limit')->nullable(); // kuniga jami yutuqlar
                $table->unsignedInteger('stock')->nullable(); // jami nechta beriladi
                $table->unsignedInteger('won_count')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('game_spins')) {
            Schema::create('game_spins', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('prize_id')->nullable();
                $table->string('result', 20); // nothing | fragment | completed | promocode | coins
                $table->unsignedInteger('coins_spent')->default(0);
                $table->json('payload')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['user_id', 'created_at']);
                $table->index(['prize_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('game_fragments')) {
            Schema::create('game_fragments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('prize_id');
                $table->unsignedTinyInteger('fragment_index');
                $table->timestamp('created_at')->nullable();

                $table->unique(['user_id', 'prize_id', 'fragment_index']);
            });
        }

        if (! Schema::hasTable('game_rewards')) {
            Schema::create('game_rewards', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('prize_id')->nullable();
                $table->string('type', 20); // promocode | book
                $table->string('title');
                $table->unsignedBigInteger('promocode_id')->nullable();
                $table->string('code', 40)->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('game_tasks')) {
            Schema::create('game_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('key', 40)->unique();
                $table->string('title_uz');
                $table->string('title_ru')->nullable();
                $table->unsignedInteger('coins')->default(5);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
            });

            $now = now();
            DB::table('game_tasks')->insert([
                ['key' => 'review', 'title_uz' => 'Kitobga sharh yozing', 'title_ru' => 'Напишите отзыв о книге', 'coins' => 5, 'is_active' => true, 'position' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'club_post', 'title_uz' => "Kitob klubida post qo'ying", 'title_ru' => 'Опубликуйте пост в книжном клубе', 'coins' => 3, 'is_active' => true, 'position' => 2, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'order', 'title_uz' => 'Buyurtma bering', 'title_ru' => 'Сделайте заказ', 'coins' => 10, 'is_active' => true, 'position' => 3, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        if (! Schema::hasTable('game_settings')) {
            Schema::create('game_settings', function (Blueprint $table) {
                $table->string('key', 40)->primary();
                $table->string('value')->nullable();
            });
            DB::table('game_settings')->insert([
                ['key' => 'enabled', 'value' => '1'],
                ['key' => 'spin_cost', 'value' => '10'],
                ['key' => 'daily_coins', 'value' => '5'],
                ['key' => 'order_coins', 'value' => '10'],
                ['key' => 'welcome_coins', 'value' => '20'],
            ]);
        }

        // Promokod qamrovi: kitob (global katalog) yoki do'kon uchun
        if (Schema::hasTable('promocodes')) {
            Schema::table('promocodes', function (Blueprint $table) {
                if (! Schema::hasColumn('promocodes', 'scope_type')) {
                    $table->string('scope_type', 10)->nullable();
                }
                if (! Schema::hasColumn('promocodes', 'scope_id')) {
                    $table->unsignedBigInteger('scope_id')->nullable();
                }
                if (! Schema::hasColumn('promocodes', 'source')) {
                    $table->string('source', 20)->nullable();
                }
            });
            // Kitob bo'laklari yig'ilganda beriladigan "1 dona bepul" kodi
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE promocodes MODIFY `type` ENUM('percent','uzs','free_item') NULL");
            }
        }
    }

    public function down(): void
    {
        foreach (['game_settings', 'game_tasks', 'game_rewards', 'game_fragments', 'game_spins', 'game_prizes', 'game_coin_transactions', 'game_wallets'] as $t) {
            Schema::dropIfExists($t);
        }
        if (Schema::hasTable('promocodes')) {
            Schema::table('promocodes', function (Blueprint $table) {
                foreach (['scope_type', 'scope_id', 'source'] as $col) {
                    if (Schema::hasColumn('promocodes', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
