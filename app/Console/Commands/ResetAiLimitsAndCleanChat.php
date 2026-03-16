<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ResetAiLimitsAndCleanChat extends Command
{
    protected $signature   = 'ai:daily-reset';
    protected $description = "Har kunlik: ai_limit ni 25 ga yangilash + eski ChatMessage/ChatMessageItem larni tozalash";

    // ── Sozlamalar ─────────────────────────────────────────────────────────────
    const AI_LIMIT          = 25;
    const CHAT_KEEP_DAYS    = 30;   // Shu kundan eski xabarlar o'chiriladi
    const CHUNK_SIZE        = 500;  // Bir vaqtda nechta user/xabar

    // =========================================================================
    //  HANDLE
    // =========================================================================

    public function handle(): void
    {
        $this->info(now()->format('d.m.Y H:i:s') . " — Kunlik AI reset boshlandi...");
        $this->newLine();

        $this->resetAiLimits();
        $this->newLine();
        $this->cleanChatMessages();

        $this->newLine();
        $this->info('✅ Jarayon muvaffaqiyatli tugadi.');
    }

    // =========================================================================
    //  1. AI LIMIT YANGILASH
    //  Faqat ai_limit < 25 bo'lgan userlarni yangilaymiz (samarali)
    // =========================================================================

    private function resetAiLimits(): void
    {
        $this->info('── AI limitlarni yangilash ──────────────────────────');

        try {
            $count = DB::table('users')
                ->where('ai_limit', '<', self::AI_LIMIT)
                ->count();

            if ($count === 0) {
                $this->info('Yangilanadigan user topilmadi (hammasi allaqachon 25 da).');
                return;
            }

            $this->info("Yangilanadigan userlar soni: {$count}");

            // Chunk bilan yangilaymiz — katta DB uchun xavfsiz
            $updated = 0;
            DB::table('users')
                ->where('ai_limit', '<', self::AI_LIMIT)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($users) use (&$updated) {
                    $ids = $users->pluck('id')->toArray();
                    DB::table('users')
                        ->whereIn('id', $ids)
                        ->update([
                            'ai_limit'   => self::AI_LIMIT,
                            'updated_at' => now(),
                        ]);
                    $updated += count($ids);
                    $this->line("  → {$updated} ta user yangilandi...");
                });

            $this->info("✓ Jami {$updated} ta user ai_limit = " . self::AI_LIMIT . " ga yangilandi.");

            Log::info('AI daily reset: limits updated', [
                'updated_count' => $updated,
                'new_limit'     => self::AI_LIMIT,
                'at'            => now()->toDateTimeString(),
            ]);

        } catch (\Throwable $e) {
            $this->error("AI limit yangilashda xatolik: {$e->getMessage()}");
            Log::error('AI daily reset — limit error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }

    // =========================================================================
    //  2. CHAT XABARLARINI TOZALASH
    //  CHAT_KEEP_DAYS kundan eski ChatMessage + bog'liq ChatMessageItem lar
    // =========================================================================

    private function cleanChatMessages(): void
    {
        $this->info('── Chat xabarlarini tozalash ────────────────────────');

        $cutoff = Carbon::now()->subDays(self::CHAT_KEEP_DAYS);
        $this->info("O'chirish chegarasi: {$cutoff->format('d.m.Y')} dan oldingi xabarlar");

        try {
            DB::table('chat_messages')
                ->where('created_at', '<', $cutoff)->delete();
            $this->info("✓ ChatMessage o'chirildi.");

            Log::info('AI daily reset: chat cleaned', [
                'deleted_messages' => $totalMessages,
                'deleted_items'    => $totalItems,
                'cutoff_date'      => $cutoff->toDateString(),
                'at'               => now()->toDateTimeString(),
            ]);

        } catch (\Throwable $e) {
            $this->error("Chat tozalashda xatolik: {$e->getMessage()}");
            Log::error('AI daily reset — chat clean error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}