<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category', 64)->index();
            $table->unsignedBigInteger('amount');
            $table->date('spent_at')->index();
            $table->string('title', 255);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('reference', 120)->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('project_settings', function (Blueprint $table) {
            $table->string('tax_mode', 20)->default('fixed')->after('packaging_threshold');
            $table->unsignedBigInteger('tax_fixed_uzs')->default(0)->after('tax_mode');
            $table->decimal('tax_profit_percent', 8, 3)->default(0)->after('tax_fixed_uzs');
            $table->decimal('payment_provider_percent', 8, 3)->default(0)->after('tax_profit_percent');
        });
    }

    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            $table->dropColumn([
                'tax_mode',
                'tax_fixed_uzs',
                'tax_profit_percent',
                'payment_provider_percent',
            ]);
        });

        Schema::dropIfExists('platform_expenses');
    }
};
