<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['books', 'stationeries'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'ai_moderation_status')) {
                    $table->string('ai_moderation_status', 32)->nullable()->after('is_approved');
                }
                if (! Schema::hasColumn($tableName, 'ai_moderation_checked_at')) {
                    $table->timestamp('ai_moderation_checked_at')->nullable()->after('ai_moderation_status');
                }
                if (! Schema::hasColumn($tableName, 'ai_moderation_note')) {
                    $table->string('ai_moderation_note', 500)->nullable()->after('ai_moderation_checked_at');
                }
                if (! Schema::hasColumn($tableName, 'ai_moderation_model')) {
                    $table->string('ai_moderation_model', 64)->nullable()->after('ai_moderation_note');
                }
                if (! Schema::hasColumn($tableName, 'ai_moderation_content_hash')) {
                    $table->char('ai_moderation_content_hash', 64)->nullable()->after('ai_moderation_model');
                }
                if (! Schema::hasColumn($tableName, 'ai_moderation_attempts')) {
                    $table->unsignedSmallInteger('ai_moderation_attempts')->default(0)->after('ai_moderation_content_hash');
                }
                if (! Schema::hasColumn($tableName, 'ai_moderation_next_retry_at')) {
                    $table->timestamp('ai_moderation_next_retry_at')->nullable()->after('ai_moderation_attempts');
                }
                if (! Schema::hasColumn($tableName, 'ai_moderation_meta')) {
                    $table->json('ai_moderation_meta')->nullable()->after('ai_moderation_next_retry_at');
                }
            });

            $index = $tableName.'_ai_moderation_queue_idx';
            if (! Schema::hasIndex($tableName, $index)) {
                Schema::table($tableName, fn (Blueprint $table) => $table->index(
                    ['ai_moderation_status', 'ai_moderation_next_retry_at'],
                    $index,
                ));
            }
        }
    }

    public function down(): void
    {
        foreach (['books', 'stationeries'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $index = $tableName.'_ai_moderation_queue_idx';
            if (Schema::hasIndex($tableName, $index)) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropIndex($index));
            }

            $columns = collect([
                'ai_moderation_status',
                'ai_moderation_checked_at',
                'ai_moderation_note',
                'ai_moderation_model',
                'ai_moderation_content_hash',
                'ai_moderation_attempts',
                'ai_moderation_next_retry_at',
                'ai_moderation_meta',
            ])->filter(fn (string $column) => Schema::hasColumn($tableName, $column))->all();

            if ($columns !== []) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
    }
};
