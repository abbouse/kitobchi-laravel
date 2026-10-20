<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kitobchi ID (OAuth 2.1 + OpenID Connect) jadvallari.
 * Kodlar, refresh tokenlar va client secretlar faqat HASH ko'rinishida saqlanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 64)->unique();
            $table->string('client_secret_hash')->nullable(); // public client — null
            $table->string('name');
            $table->string('logo_url')->nullable();
            $table->string('homepage_url')->nullable();
            $table->string('type', 16)->default('confidential'); // confidential | public
            $table->boolean('first_party')->default(false);       // rozilik oynasi so'ralmaydi, sub = user id
            $table->json('redirect_uris');
            $table->json('post_logout_redirect_uris')->nullable();
            $table->json('allowed_scopes');
            $table->string('sector_identifier')->nullable();       // pairwise sub uchun (odatda domen)
            $table->string('status', 16)->default('active');       // active | disabled
            $table->unsignedBigInteger('seller_id')->nullable()->index();
            $table->string('contact_email')->nullable();
            $table->timestamps();
        });

        Schema::create('id_auth_codes', function (Blueprint $table) {
            $table->id();
            $table->char('code_hash', 64)->unique();
            $table->unsignedBigInteger('client_pk')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('redirect_uri');
            $table->string('scope', 500);
            $table->string('code_challenge', 128);
            $table->string('nonce', 255)->nullable();
            $table->unsignedBigInteger('auth_time');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('id_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->char('token_hash', 64)->unique();
            $table->char('family_id', 32)->index();
            $table->unsignedBigInteger('client_pk')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('auth_code_id')->nullable()->index();
            $table->string('scope', 500);
            $table->unsignedBigInteger('auth_time');
            $table->timestamp('expires_at');
            $table->timestamp('family_expires_at');
            $table->timestamp('used_at')->nullable();     // rotatsiya qilingan
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('id_consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_pk');
            $table->json('scopes');
            $table->timestamps();
            $table->unique(['user_id', 'client_pk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_consents');
        Schema::dropIfExists('id_refresh_tokens');
        Schema::dropIfExists('id_auth_codes');
        Schema::dropIfExists('id_clients');
    }
};
