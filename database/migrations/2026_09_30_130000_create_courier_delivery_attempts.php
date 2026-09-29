<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kitob buziladigan mahsulot emas: mijoz topilmasa buyurtma bekor
 * qilinmaydi, kuryerda qoladi va keyingi kun qayta urinishga qo'yiladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courier_delivery_attempts')) {
            Schema::create('courier_delivery_attempts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('courier_order_id')->index();
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('courier_id')->index();
                $table->unsignedTinyInteger('attempt_no')->default(1);
                $table->string('reason', 40);
                $table->string('note', 500)->nullable();
                $table->dateTime('next_attempt_at')->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lon', 10, 7)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('courier_orders')) {
            Schema::table('courier_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('courier_orders', 'delivery_attempts')) {
                    $table->unsignedTinyInteger('delivery_attempts')->default(0);
                }
                if (! Schema::hasColumn('courier_orders', 'next_attempt_at')) {
                    $table->dateTime('next_attempt_at')->nullable()->index();
                }
                if (! Schema::hasColumn('courier_orders', 'last_attempt_reason')) {
                    $table->string('last_attempt_reason', 40)->nullable();
                }
                if (! Schema::hasColumn('courier_orders', 'return_required_at')) {
                    $table->dateTime('return_required_at')->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('project_settings') && ! Schema::hasColumn('project_settings', 'courier_max_delivery_attempts')) {
            Schema::table('project_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('courier_max_delivery_attempts')->default(3);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_delivery_attempts');
        if (Schema::hasTable('courier_orders')) {
            Schema::table('courier_orders', function (Blueprint $table) {
                foreach (['delivery_attempts', 'next_attempt_at', 'last_attempt_reason', 'return_required_at'] as $column) {
                    if (Schema::hasColumn('courier_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
        if (Schema::hasTable('project_settings') && Schema::hasColumn('project_settings', 'courier_max_delivery_attempts')) {
            Schema::table('project_settings', fn (Blueprint $table) => $table->dropColumn('courier_max_delivery_attempts'));
        }
    }
};
