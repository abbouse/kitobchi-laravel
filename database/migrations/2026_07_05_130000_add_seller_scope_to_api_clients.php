<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('api_clients')) {
            return;
        }

        Schema::table('api_clients', function (Blueprint $table) {
            if (! Schema::hasColumn('api_clients', 'seller_id')) {
                // Kalit bitta sellerga bog'langan bo'lsa — u faqat o'sha do'kon
                // mahsulotlarini boshqara oladi (write). NULL = platforma kaliti.
                $table->unsignedBigInteger('seller_id')->nullable()->after('name')->index();
            }
            if (! Schema::hasColumn('api_clients', 'allowed_ips')) {
                // Bo'sh/NULL = istalgan IP. To'ldirilsa — faqat shu IP/CIDR'lar.
                $table->json('allowed_ips')->nullable()->after('abilities');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('api_clients')) {
            return;
        }

        Schema::table('api_clients', function (Blueprint $table) {
            if (Schema::hasColumn('api_clients', 'seller_id')) {
                $table->dropColumn('seller_id');
            }
            if (Schema::hasColumn('api_clients', 'allowed_ips')) {
                $table->dropColumn('allowed_ips');
            }
        });
    }
};
