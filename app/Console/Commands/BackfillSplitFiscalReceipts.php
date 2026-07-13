<?php

namespace App\Console\Commands;

use App\Jobs\RegisterTransactionFiscalReceiptJob;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class BackfillSplitFiscalReceipts extends Command
{
    protected $signature = 'split:backfill-fiscal-receipts {--limit=100 : Bir yurishda navbatga qo\'yiladigan tranzaksiyalar}';

    protected $description = "Fiskal cheki yo'q muvaffaqiyatli split tranzaksiyalarini OFD navbatiga qo'yadi";

    public function handle(): int
    {
        if (! config('services.paylov.ofd.enabled', false) || ! Schema::hasTable('transactions')) {
            $this->info('OFD o\'chirilgan yoki transactions jadvali mavjud emas.');

            return self::SUCCESS;
        }

        $limit = max(1, min(500, (int) $this->option('limit')));
        $transactions = Transaction::query()
            ->where('payment_type', 'split')
            ->where('provider', 'paylov')
            ->where('state', 2)
            ->whereNotNull('order_id')
            ->whereNotNull('provider_transaction_id')
            ->where('provider_transaction_id', '<>', '')
            ->where(function ($query) {
                $query->whereNull('perform_fiscal_data')
                    ->orWhereNull('perform_fiscal_data->qr_code_url');
            })
            ->orderBy('id')
            ->limit($limit * 3)
            ->get(['id', 'perform_fiscal_data'])
            ->filter(function (Transaction $transaction) {
                $perform = is_array($transaction->perform_fiscal_data)
                    ? $transaction->perform_fiscal_data
                    : [];

                return (int) ($perform['attempts'] ?? 0) < 5;
            })
            ->take($limit);

        $queued = 0;
        foreach ($transactions as $transaction) {
            if (! Cache::add("ofd:split-command:{$transaction->id}", 1, now()->addHours(6))) {
                continue;
            }

            RegisterTransactionFiscalReceiptJob::dispatch((int) $transaction->id);
            $queued++;
        }

        $this->info("Navbatga qo'yildi: {$queued}");

        return self::SUCCESS;
    }
}
