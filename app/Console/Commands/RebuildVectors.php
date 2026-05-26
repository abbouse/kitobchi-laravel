<?php

namespace App\Console\Commands;

use App\Services\ProductVectorService;
use Illuminate\Console\Command;

class RebuildVectors extends Command
{
    protected $signature = 'vectors:rebuild {--type=all : book|stationery|all} {--force : Active mahsulotlarning hammasini qayta vector qiladi} {--limit=120 : Bir yurishda nechta mahsulotni sync qilish}';

    protected $description = 'Faol mahsulotlar uchun vectorData yaratadi, nofaol mahsulotlardagi vectorData ni tozalaydi';

    public function __construct(
        private readonly ProductVectorService $vectorService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $type = strtolower((string) $this->option('type'));
        $force = (bool) $this->option('force');
        $limit = max(0, (int) $this->option('limit'));

        $types = match ($type) {
            'all' => ['book', 'stationery'],
            'book', 'books' => ['book'],
            'stationery', 'stationeries' => ['stationery'],
            default => null,
        };

        if ($types === null) {
            $this->error("Noto'g'ri type: {$type}. book | stationery | all ishlating.");
            return self::INVALID;
        }

        foreach ($types as $vectorType) {
            $result = $this->vectorService->rebuildType($vectorType, $force, $limit);

            $this->info(sprintf(
                '%s: %d ta sync, %d ta inactive vector tozalandi.',
                $vectorType === 'book' ? 'Books' : 'Stationery',
                $result['synced'],
                $result['cleared'],
            ));
        }

        $this->info('Vector oqimi yakunlandi.');

        return self::SUCCESS;
    }
}
