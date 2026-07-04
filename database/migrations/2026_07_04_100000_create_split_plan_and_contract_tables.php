<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('split_plans')) {
            Schema::create('split_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                // Muddat oylarda: 1..36 (masalan 2, 4, 6 oy)
                $table->unsignedTinyInteger('months');
                // To'lov chastotasi: month | week
                $table->string('period_unit', 8)->default('month');
                // Har nechchi birlikda: month+1 => har oy, week+2 => har 2 hafta
                $table->unsignedTinyInteger('period_every')->default(1);
                // Marketplace uslubi: umumiy ustama = principal * % * months.
                // 0 bo'lsa foizsiz tarif.
                $table->decimal('monthly_interest_percent', 5, 2)->default(0);
                // Tarif darajasidagi min/max buyurtma summasi (global sozlamani override qiladi)
                $table->unsignedBigInteger('min_order_sum')->nullable();
                $table->unsignedBigInteger('max_order_sum')->nullable();
                // Uzoq muddatli tariflarni faqat yuqori ishonchli userlarga ochish
                $table->decimal('min_confidence_score', 5, 2)->nullable();
                $table->boolean('enabled')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['enabled', 'sort_order']);
            });
        }

        if (! Schema::hasTable('split_contracts')) {
            Schema::create('split_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->foreignId('plan_id')->nullable()->constrained('split_plans')->nullOnDelete();

                $table->unsignedBigInteger('principal_amount');
                $table->unsignedBigInteger('interest_amount')->default(0);
                $table->unsignedBigInteger('total_amount');
                $table->unsignedBigInteger('paid_amount')->default(0);
                $table->unsignedBigInteger('remaining_amount');

                // Plan snapshot (plan keyin o'zgarsa ham shartnoma shartlari qotib qoladi)
                $table->unsignedTinyInteger('months');
                $table->string('period_unit', 8)->default('month');
                $table->unsignedTinyInteger('period_every')->default(1);
                $table->decimal('monthly_interest_percent', 5, 2)->default(0);
                $table->unsignedTinyInteger('installments_count');
                // Oylik jadvalda yechiladigan kun (1..31, oy oxiriga clamp qilinadi)
                $table->unsignedTinyInteger('debit_day')->nullable();

                // pending -> active -> completed | cancelled; active <-> overdue; overdue -> defaulted
                $table->string('status', 16)->default('pending')->index();
                $table->timestamp('starts_at');
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('overdue_since')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->json('snapshot')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('split_installments')) {
            Schema::create('split_installments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained('split_contracts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('sequence');
                $table->unsignedBigInteger('amount');
                $table->unsignedBigInteger('paid_amount')->default(0);
                $table->boolean('is_upfront')->default(false);
                $table->timestamp('due_at');
                $table->timestamp('paid_at')->nullable();
                // pending | paid | overdue | waived | cancelled
                $table->string('status', 16)->default('pending');
                $table->unsignedTinyInteger('attempt_count')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('next_attempt_at')->nullable();
                $table->unsignedBigInteger('transaction_id')->nullable();
                $table->string('provider_transaction_id', 128)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['contract_id', 'sequence']);
                $table->index(['status', 'due_at']);
                $table->index(['status', 'next_attempt_at']);
            });
        }

        if (! Schema::hasTable('split_events')) {
            Schema::create('split_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('contract_id')->nullable()->index();
                $table->unsignedBigInteger('installment_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('type', 48)->index();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('split_events');
        Schema::dropIfExists('split_installments');
        Schema::dropIfExists('split_contracts');
        Schema::dropIfExists('split_plans');
    }
};
