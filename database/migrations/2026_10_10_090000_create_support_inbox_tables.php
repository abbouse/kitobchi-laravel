<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Support inbox (boshqaruv): operatorlar faqat paneldan javob beradi.
 * - bot_tickets: biriktirilgan admin, oxirgi xabar vaqti, o'qilmaganlar, birinchi javob vaqti, yaxshi/yomon baho
 * - bot_ticket_attachments: xabarga bog'lash
 * - seller_support_tickets: birinchi javob vaqti, baho
 * - seller_support_ticket_messages: ichki eslatma (mijozga ko'rinmaydi)
 * - support_templates: shablon javoblar
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_tickets')) {
            Schema::table('bot_tickets', function (Blueprint $table) {
                if (! Schema::hasColumn('bot_tickets', 'admin_id')) {
                    $table->unsignedBigInteger('admin_id')->nullable()->after('operator_id')->index();
                }
                if (! Schema::hasColumn('bot_tickets', 'last_message_at')) {
                    $table->timestamp('last_message_at')->nullable()->index();
                }
                if (! Schema::hasColumn('bot_tickets', 'admin_unread_count')) {
                    $table->unsignedInteger('admin_unread_count')->default(0);
                }
                if (! Schema::hasColumn('bot_tickets', 'first_response_at')) {
                    $table->timestamp('first_response_at')->nullable();
                }
                if (! Schema::hasColumn('bot_tickets', 'feedback')) {
                    $table->string('feedback', 8)->nullable()->index();
                }
                if (! Schema::hasColumn('bot_tickets', 'feedback_at')) {
                    $table->timestamp('feedback_at')->nullable();
                }
                if (! Schema::hasColumn('bot_tickets', 'feedback_requested_at')) {
                    $table->timestamp('feedback_requested_at')->nullable();
                }
            });

            if (Schema::hasTable('bot_ticket_messages')) {
                DB::statement('UPDATE bot_tickets t SET last_message_at = COALESCE((SELECT MAX(m.created_at) FROM bot_ticket_messages m WHERE m.ticket_id = t.id), t.updated_at, t.created_at) WHERE t.last_message_at IS NULL');
                DB::statement("UPDATE bot_tickets t SET first_response_at = (SELECT MIN(m.created_at) FROM bot_ticket_messages m WHERE m.ticket_id = t.id AND m.sent_by IN ('operator','admin') AND m.message_type <> 'note') WHERE t.first_response_at IS NULL");
            }
        }

        if (Schema::hasTable('bot_ticket_attachments') && ! Schema::hasColumn('bot_ticket_attachments', 'message_id')) {
            Schema::table('bot_ticket_attachments', function (Blueprint $table) {
                $table->unsignedBigInteger('message_id')->nullable()->after('ticket_id')->index();
            });
        }

        if (Schema::hasTable('seller_support_tickets')) {
            Schema::table('seller_support_tickets', function (Blueprint $table) {
                if (! Schema::hasColumn('seller_support_tickets', 'first_response_at')) {
                    $table->timestamp('first_response_at')->nullable();
                }
                if (! Schema::hasColumn('seller_support_tickets', 'feedback')) {
                    $table->string('feedback', 8)->nullable()->index();
                }
                if (! Schema::hasColumn('seller_support_tickets', 'feedback_at')) {
                    $table->timestamp('feedback_at')->nullable();
                }
            });

            if (Schema::hasTable('seller_support_ticket_messages')) {
                DB::statement("UPDATE seller_support_tickets t SET first_response_at = (SELECT MIN(m.created_at) FROM seller_support_ticket_messages m WHERE m.ticket_id = t.id AND m.sender_type = 'admin') WHERE t.first_response_at IS NULL");
            }
        }

        if (Schema::hasTable('seller_support_ticket_messages') && ! Schema::hasColumn('seller_support_ticket_messages', 'is_internal')) {
            Schema::table('seller_support_ticket_messages', function (Blueprint $table) {
                $table->boolean('is_internal')->default(false)->after('message');
            });
        }

        if (! Schema::hasTable('support_templates')) {
            Schema::create('support_templates', function (Blueprint $table) {
                $table->id();
                $table->string('title', 120);
                $table->text('body');
                $table->string('audience', 16)->default('all'); // all | customer | shop
                $table->string('category', 40)->nullable();
                $table->string('shortcut', 32)->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('usage_count')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['is_active', 'audience']);
            });

            $this->seedTemplates();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_templates');

        if (Schema::hasTable('seller_support_ticket_messages') && Schema::hasColumn('seller_support_ticket_messages', 'is_internal')) {
            Schema::table('seller_support_ticket_messages', fn (Blueprint $table) => $table->dropColumn('is_internal'));
        }

        if (Schema::hasTable('seller_support_tickets')) {
            Schema::table('seller_support_tickets', function (Blueprint $table) {
                foreach (['first_response_at', 'feedback', 'feedback_at'] as $column) {
                    if (Schema::hasColumn('seller_support_tickets', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('bot_ticket_attachments') && Schema::hasColumn('bot_ticket_attachments', 'message_id')) {
            Schema::table('bot_ticket_attachments', fn (Blueprint $table) => $table->dropColumn('message_id'));
        }

        if (Schema::hasTable('bot_tickets')) {
            Schema::table('bot_tickets', function (Blueprint $table) {
                foreach (['admin_id', 'last_message_at', 'admin_unread_count', 'first_response_at', 'feedback', 'feedback_at', 'feedback_requested_at'] as $column) {
                    if (Schema::hasColumn('bot_tickets', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function seedTemplates(): void
    {
        $rows = [
            ['Salomlashish', 'Assalomu alaykum, {name}! Kitobchi qo\'llab-quvvatlash xizmati. Sizga qanday yordam bera olaman?', 'all', 'umumiy', 'salom'],
            ['Kutib turing', 'Iltimos, bir oz kutib turing — ma\'lumotni tekshirib, hozir javob beraman.', 'all', 'umumiy', 'kut'],
            ['Buyurtma raqami', 'Buyurtma raqamingizni yuborsangiz, holatini darhol tekshirib beraman.', 'customer', 'buyurtma', 'raqam'],
            ['Buyurtmani kuzatish', 'Buyurtmangiz holatini ilovadagi "Buyurtmalarim" bo\'limida real vaqtda kuzatishingiz mumkin. Savol bo\'lsa, shu yerga yozing.', 'customer', 'buyurtma', 'holat'],
            ['Yetkazish muddati', 'Toshkent bo\'ylab yetkazish odatda 1–2 kun, viloyatlarga 2–5 kun davom etadi. Aniq muddat buyurtma sahifasida ko\'rsatiladi.', 'customer', 'yetkazish', 'muddat'],
            ['Buyurtmani bekor qilish', 'Buyurtma hali jo\'natilmagan bo\'lsa, uni ilovadagi buyurtma sahifasidan bekor qilishingiz mumkin. Pul 1–3 ish kunida kartangizga qaytadi.', 'customer', 'buyurtma', 'bekor'],
            ['To\'lov muammosi', 'To\'lov o\'tmagan bo\'lsa, karta balansini va SMS tasdiqlashni tekshirib, qayta urinib ko\'ring. Pul yechilgan, lekin buyurtma ko\'rinmasa — chek skrinshotini yuboring.', 'customer', 'tolov', 'tolov'],
            ['Keshbek', 'Keshbek buyurtma yetkazilgandan keyin hisobingizga tushadi va keyingi xaridlarda chegirma sifatida ishlatiladi.', 'customer', 'keshbek', 'keshbek'],
            ['Do\'kon: mahsulot moderatsiyasi', 'Mahsulotingiz moderatsiyada. Odatda 24 soat ichida ko\'rib chiqiladi. Rad etilsa, sababi ilovada ko\'rsatiladi.', 'shop', 'katalog', 'moderatsiya'],
            ['Do\'kon: pul yechish', 'Pul yechish so\'rovi 1–3 ish kunida ko\'rib chiqiladi. Bank rekvizitlaringiz to\'g\'ri kiritilganini tekshiring.', 'shop', 'moliya', 'pul'],
            ['Do\'kon: buyurtma tayyorlash', 'Buyurtmani ilovada "Tayyor" deb belgilang — kuryer avtomatik biriktiriladi va mahsulotni olib ketadi.', 'shop', 'buyurtma', 'tayyor'],
            ['Hal qilindi', 'Masala hal qilindi. Yana savollaringiz bo\'lsa, bemalol yozing. Kitobchi\'ni tanlaganingiz uchun rahmat!', 'all', 'umumiy', 'rahmat'],
        ];

        $now = now();
        foreach ($rows as $i => [$title, $body, $audience, $category, $shortcut]) {
            DB::table('support_templates')->insert([
                'title' => $title,
                'body' => $body,
                'audience' => $audience,
                'category' => $category,
                'shortcut' => $shortcut,
                'sort' => $i,
                'is_active' => true,
                'usage_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
