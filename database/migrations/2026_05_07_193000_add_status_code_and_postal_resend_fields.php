<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solds', function (Blueprint $table) {
            $table->string('status_code', 32)->nullable()->after('status')->index();
            $table->string('payment_status_code', 32)->nullable()->after('paymentStatus')->index();
            $table->string('order_kind', 32)->default('standard')->after('payment_status_code')->index();
            $table->string('postal_return_status', 32)->default('none')->after('order_kind')->index();
            $table->unsignedInteger('postal_return_fee')->nullable()->after('postal_return_status');
            $table->text('postal_return_note')->nullable()->after('postal_return_fee');
            $table->unsignedBigInteger('resend_source_order_id')->nullable()->after('postal_return_note');
            $table->unsignedBigInteger('resend_replacement_order_id')->nullable()->after('resend_source_order_id');
            $table->timestamp('resend_available_at')->nullable()->after('resend_replacement_order_id');
        });

        Schema::table('seller_orders', function (Blueprint $table) {
            $table->string('status_code', 32)->nullable()->after('status')->index();
        });

        Schema::table('courier_orders', function (Blueprint $table) {
            $table->string('status_code', 32)->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('courier_orders', function (Blueprint $table) {
            $table->dropColumn('status_code');
        });

        Schema::table('seller_orders', function (Blueprint $table) {
            $table->dropColumn('status_code');
        });

        Schema::table('solds', function (Blueprint $table) {
            $table->dropColumn([
                'status_code',
                'payment_status_code',
                'order_kind',
                'postal_return_status',
                'postal_return_fee',
                'postal_return_note',
                'resend_source_order_id',
                'resend_replacement_order_id',
                'resend_available_at',
            ]);
        });
    }
};
