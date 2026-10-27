<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jamoa Telegram boti (buyurtma xabarlari + vazifalar).
 *
 *  - admins: Telegram akkauntini bog'lash (bot tugmalarini kim bosganini bilish uchun)
 *  - team_bot_posts: guruhga yuborilgan xabar ↔ buyurtma/vazifa (keyin tahrirlash uchun)
 *  - team_tasks: jamoa vazifalari (boshqaruv + Telegram'da bir xil)
 *  - team_bot_settings: guruh mavzulari (topic) ID'lari va boshqa sozlamalar
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (! Schema::hasColumn('admins', 'telegram_user_id')) {
                $table->unsignedBigInteger('telegram_user_id')->nullable()->unique()->after('last_login_at');
                $table->string('telegram_username', 64)->nullable()->after('telegram_user_id');
                $table->string('telegram_link_token', 64)->nullable()->index()->after('telegram_username');
                $table->timestamp('telegram_link_expires_at')->nullable()->after('telegram_link_token');
            }
        });

        Schema::create('team_bot_posts', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16);                 // order | task
            $table->unsignedBigInteger('ref_id');
            $table->bigInteger('chat_id');
            $table->unsignedBigInteger('thread_id')->nullable();
            $table->unsignedBigInteger('message_id');
            $table->string('content_hash', 64)->nullable(); // bir xil matnni qayta tahrirlamaslik uchun
            $table->json('meta')->nullable();               // masalan: mas'ul admin, SLA eslatmasi yuborilgani
            $table->timestamps();
            $table->unique(['kind', 'ref_id']);
        });

        Schema::create('team_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 16)->default('todo');      // todo | doing | done | cancelled
            $table->string('priority', 16)->default('normal');  // low | normal | high
            $table->timestamp('due_at')->nullable();
            $table->foreignId('assignee_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('source', 16)->default('panel');     // panel | telegram
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'assignee_admin_id']);
        });

        Schema::create('team_bot_settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_bot_settings');
        Schema::dropIfExists('team_tasks');
        Schema::dropIfExists('team_bot_posts');
        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'telegram_user_id')) {
                $table->dropUnique(['telegram_user_id']);
                $table->dropColumn(['telegram_user_id', 'telegram_username', 'telegram_link_token', 'telegram_link_expires_at']);
            }
        });
    }
};
