<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_settings')) {
            Schema::table('project_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('project_settings', 'paylov_refund_sender_card_id')) {
                    $table->string('paylov_refund_sender_card_id')->nullable()->after('split_card_delete_lock_enabled');
                }
                if (! Schema::hasColumn('project_settings', 'paylov_refund_service_id')) {
                    $table->string('paylov_refund_service_id')->nullable()->after('paylov_refund_sender_card_id');
                }
            });
        }

        if (Schema::hasTable('solds')) {
            Schema::table('solds', function (Blueprint $table) {
                if (! Schema::hasColumn('solds', 'cancel_reason_code')) {
                    $table->string('cancel_reason_code', 64)->nullable()->after('recipient_address');
                }
                if (! Schema::hasColumn('solds', 'cancel_note_uz')) {
                    $table->text('cancel_note_uz')->nullable()->after('cancel_reason_code');
                }
                if (! Schema::hasColumn('solds', 'cancel_note_ru')) {
                    $table->text('cancel_note_ru')->nullable()->after('cancel_note_uz');
                }
                if (! Schema::hasColumn('solds', 'cancel_note_en')) {
                    $table->text('cancel_note_en')->nullable()->after('cancel_note_ru');
                }
                if (! Schema::hasColumn('solds', 'cancel_note_ja')) {
                    $table->text('cancel_note_ja')->nullable()->after('cancel_note_en');
                }
                if (! Schema::hasColumn('solds', 'cancelled_by_seller_id')) {
                    $table->unsignedBigInteger('cancelled_by_seller_id')->nullable()->after('cancel_note_ja');
                }
                if (! Schema::hasColumn('solds', 'refund_total_amount')) {
                    $table->unsignedInteger('refund_total_amount')->default(0)->after('cancelled_by_seller_id');
                }
            });
        }

        if (Schema::hasTable('seller_orders')) {
            Schema::table('seller_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('seller_orders', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('accepted_at');
                }
                if (! Schema::hasColumn('seller_orders', 'cancelled_by_seller_id')) {
                    $table->unsignedBigInteger('cancelled_by_seller_id')->nullable()->after('cancelled_at');
                }
                if (! Schema::hasColumn('seller_orders', 'cancel_reason_code')) {
                    $table->string('cancel_reason_code', 64)->nullable()->after('cancelled_by_seller_id');
                }
                if (! Schema::hasColumn('seller_orders', 'cancel_note_uz')) {
                    $table->text('cancel_note_uz')->nullable()->after('cancel_reason_code');
                }
                if (! Schema::hasColumn('seller_orders', 'cancel_note_ru')) {
                    $table->text('cancel_note_ru')->nullable()->after('cancel_note_uz');
                }
                if (! Schema::hasColumn('seller_orders', 'cancel_note_en')) {
                    $table->text('cancel_note_en')->nullable()->after('cancel_note_ru');
                }
                if (! Schema::hasColumn('seller_orders', 'cancel_note_ja')) {
                    $table->text('cancel_note_ja')->nullable()->after('cancel_note_en');
                }
                if (! Schema::hasColumn('seller_orders', 'custom_cancel_note')) {
                    $table->text('custom_cancel_note')->nullable()->after('cancel_note_ja');
                }
                if (! Schema::hasColumn('seller_orders', 'refund_status')) {
                    $table->string('refund_status', 32)->nullable()->after('custom_cancel_note');
                }
            });
        }

        if (Schema::hasTable('seller_order_items')) {
            Schema::table('seller_order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('seller_order_items', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('price');
                }
                if (! Schema::hasColumn('seller_order_items', 'cancelled_by_seller_id')) {
                    $table->unsignedBigInteger('cancelled_by_seller_id')->nullable()->after('cancelled_at');
                }
                if (! Schema::hasColumn('seller_order_items', 'cancel_reason_code')) {
                    $table->string('cancel_reason_code', 64)->nullable()->after('cancelled_by_seller_id');
                }
                if (! Schema::hasColumn('seller_order_items', 'cancel_note_uz')) {
                    $table->text('cancel_note_uz')->nullable()->after('cancel_reason_code');
                }
                if (! Schema::hasColumn('seller_order_items', 'cancel_note_ru')) {
                    $table->text('cancel_note_ru')->nullable()->after('cancel_note_uz');
                }
                if (! Schema::hasColumn('seller_order_items', 'cancel_note_en')) {
                    $table->text('cancel_note_en')->nullable()->after('cancel_note_ru');
                }
                if (! Schema::hasColumn('seller_order_items', 'cancel_note_ja')) {
                    $table->text('cancel_note_ja')->nullable()->after('cancel_note_en');
                }
                if (! Schema::hasColumn('seller_order_items', 'custom_cancel_note')) {
                    $table->text('custom_cancel_note')->nullable()->after('cancel_note_ja');
                }
                if (! Schema::hasColumn('seller_order_items', 'refund_status')) {
                    $table->string('refund_status', 32)->nullable()->after('custom_cancel_note');
                }
                if (! Schema::hasColumn('seller_order_items', 'refunded_at')) {
                    $table->timestamp('refunded_at')->nullable()->after('refund_status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seller_order_items')) {
            Schema::table('seller_order_items', function (Blueprint $table) {
                foreach ([
                    'cancelled_at',
                    'cancelled_by_seller_id',
                    'cancel_reason_code',
                    'cancel_note_uz',
                    'cancel_note_ru',
                    'cancel_note_en',
                    'cancel_note_ja',
                    'custom_cancel_note',
                    'refund_status',
                    'refunded_at',
                ] as $column) {
                    if (Schema::hasColumn('seller_order_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('seller_orders')) {
            Schema::table('seller_orders', function (Blueprint $table) {
                foreach ([
                    'cancelled_at',
                    'cancelled_by_seller_id',
                    'cancel_reason_code',
                    'cancel_note_uz',
                    'cancel_note_ru',
                    'cancel_note_en',
                    'cancel_note_ja',
                    'custom_cancel_note',
                    'refund_status',
                ] as $column) {
                    if (Schema::hasColumn('seller_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('solds')) {
            Schema::table('solds', function (Blueprint $table) {
                foreach ([
                    'cancel_reason_code',
                    'cancel_note_uz',
                    'cancel_note_ru',
                    'cancel_note_en',
                    'cancel_note_ja',
                    'cancelled_by_seller_id',
                    'refund_total_amount',
                ] as $column) {
                    if (Schema::hasColumn('solds', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('project_settings')) {
            Schema::table('project_settings', function (Blueprint $table) {
                foreach ([
                    'paylov_refund_sender_card_id',
                    'paylov_refund_service_id',
                ] as $column) {
                    if (Schema::hasColumn('project_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
