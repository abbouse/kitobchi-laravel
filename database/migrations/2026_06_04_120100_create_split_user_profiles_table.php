<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('split_user_profiles')) {
            return;
        }

        Schema::create('split_user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('eligible')->default(false);
            $table->json('eligibility_reasons')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->unsignedBigInteger('computed_limit')->default(0);
            $table->unsignedBigInteger('available_limit')->default(0);
            $table->unsignedBigInteger('active_exposure')->default(0);
            $table->unsignedTinyInteger('max_active_contracts')->default(0);
            $table->unsignedTinyInteger('active_contract_count')->default(0);
            $table->decimal('reputation_score', 5, 2)->default(0);
            $table->unsignedSmallInteger('cod_return_strikes')->default(0);
            $table->unsignedSmallInteger('account_age_days')->default(0);
            $table->unsignedSmallInteger('verified_card_age_days')->default(0);
            $table->unsignedTinyInteger('verified_cards_count')->default(0);
            $table->unsignedSmallInteger('successful_card_payments_180d')->default(0);
            $table->unsignedSmallInteger('completed_orders_90d')->default(0);
            $table->unsignedSmallInteger('completed_orders_all')->default(0);
            $table->unsignedBigInteger('completed_gmv_180d')->default(0);
            $table->decimal('cancel_rate_90d', 5, 4)->default(0);
            $table->unsignedSmallInteger('device_count_90d')->default(0);
            $table->unsignedSmallInteger('card_churn_90d')->default(0);
            $table->unsignedTinyInteger('active_warning_count')->default(0);
            $table->timestamp('last_refreshed_at')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_user_profiles');
    }
};
