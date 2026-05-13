<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hubs')) {
            Schema::create('hubs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('country_code', 8)->default('UZ');
                $table->string('region_name')->nullable();
                $table->string('city_name')->nullable();
                $table->string('address')->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lon', 10, 7)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_primary')->default(false);
                $table->boolean('supports_first_mile')->default(true);
                $table->boolean('supports_last_mile')->default(true);
                $table->boolean('supports_postal_dispatch')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hub_staff')) {
            Schema::create('hub_staff', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hub_id')->constrained('hubs')->cascadeOnDelete();
                $table->string('staffable_type');
                $table->unsignedBigInteger('staffable_id');
                $table->string('role');
                $table->boolean('is_active')->default(true);
                $table->json('permissions')->nullable();
                $table->timestamps();

                $table->index(['staffable_type', 'staffable_id']);
                $table->unique(['hub_id', 'staffable_type', 'staffable_id', 'role'], 'hub_staff_unique_role');
            });
        }

        if (!Schema::hasTable('order_fulfillments')) {
            Schema::create('order_fulfillments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('solds')->cascadeOnDelete();
                $table->foreignId('hub_id')->nullable()->constrained('hubs')->nullOnDelete();
                $table->string('status_code')->default('awaiting_seller_prep');
                $table->string('first_mile_mode')->default('courier_pickup');
                $table->string('last_mile_mode')->default('courier_delivery');
                $table->timestamp('seller_prepared_at')->nullable();
                $table->timestamp('ready_for_pickup_at')->nullable();
                $table->timestamp('picked_from_seller_at')->nullable();
                $table->timestamp('arrived_at_hub_at')->nullable();
                $table->timestamp('qc_checked_at')->nullable();
                $table->timestamp('packed_at')->nullable();
                $table->timestamp('labeled_at')->nullable();
                $table->timestamp('dispatched_to_post_at')->nullable();
                $table->timestamp('assigned_last_mile_at')->nullable();
                $table->timestamp('out_for_delivery_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('returned_at')->nullable();
                $table->string('postal_tracking_number')->nullable();
                $table->string('label_code')->nullable();
                $table->json('notes')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique('order_id');
                $table->index(['hub_id', 'status_code']);
                $table->index(['status_code', 'ready_for_pickup_at']);
            });
        }

        if (!Schema::hasTable('courier_tasks')) {
            Schema::create('courier_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('solds')->cascadeOnDelete();
                $table->foreignId('fulfillment_id')->nullable()->constrained('order_fulfillments')->cascadeOnDelete();
                $table->foreignId('hub_id')->nullable()->constrained('hubs')->nullOnDelete();
                $table->unsignedInteger('seller_id')->nullable();
                $table->unsignedInteger('courier_id')->nullable();
                $table->string('leg')->default('first_mile');
                $table->string('status_code')->default('assigned');
                $table->json('pickup_address')->nullable();
                $table->json('dropoff_address')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('arrived_at')->nullable();
                $table->timestamp('picked_up_at')->nullable();
                $table->timestamp('dropped_off_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->unsignedInteger('fee_amount')->default(0);
                $table->string('settlement_status')->default('pending');
                $table->timestamp('settled_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['courier_id', 'status_code']);
                $table->index(['hub_id', 'leg', 'status_code']);
                $table->index(['seller_id', 'leg', 'status_code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_tasks');
        Schema::dropIfExists('order_fulfillments');
        Schema::dropIfExists('hub_staff');
        Schema::dropIfExists('hubs');
    }
};
