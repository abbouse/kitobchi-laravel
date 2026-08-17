<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instagram_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_user_id');
            $table->string('username')->nullable();
            $table->string('type')->default('general'); // partnership, general, order_inquiry, complaint
            $table->text('message');
            $table->text('admin_reply')->nullable();
            $table->string('status')->default('pending'); // pending, replied, closed
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index('instagram_user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_inquiries');
    }
};
