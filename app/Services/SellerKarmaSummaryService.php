<?php

namespace App\Services;

use App\Models\Books;
use App\Models\Seller;
use App\Models\SellerTransaction;
use App\Models\Stationery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SellerKarmaSummaryService
{
    public function __construct(
        private readonly SellerReputationService $sellerReputationService,
    ) {
    }

    public function cachedSummary(Seller $seller, int $minutes = 3): array
    {
        return Cache::remember(
            "seller:{$seller->id}:menu_reputation_summary:v3",
            now()->addMinutes($minutes),
            fn () => $this->buildSummary($seller),
        );
    }

    public function buildSummary(Seller $seller): array
    {
        $reputation = $this->sellerReputationService->recalculateSeller($seller, false);
        $metrics = $reputation['metrics'] ?? [];

        $bookAvg = (float) (Books::query()
            ->where('seller_id', $seller->id)
            ->where('is_hidden', 0)
            ->where('ugc_aggregate_score', '>', 0)
            ->avg('ugc_aggregate_score') ?? 0);

        $stationeryAvg = (float) (Stationery::query()
            ->where('seller_id', $seller->id)
            ->where('is_hidden', 0)
            ->where('ugc_aggregate_score', '>', 0)
            ->avg('ugc_aggregate_score') ?? 0);

        $bookCount = (int) Books::query()
            ->where('seller_id', $seller->id)
            ->where('is_hidden', 0)
            ->count();

        $stationeryCount = (int) Stationery::query()
            ->where('seller_id', $seller->id)
            ->where('is_hidden', 0)
            ->count();

        $activeBooks = (int) Books::query()
            ->where('seller_id', $seller->id)
            ->where('is_hidden', 0)
            ->where('status', 1)
            ->where('count', '>', 0)
            ->count();

        $activeStationery = (int) Stationery::query()
            ->where('seller_id', $seller->id)
            ->where('is_hidden', 0)
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->count();

        $ratedSources = collect([$bookAvg, $stationeryAvg])->filter(fn ($value) => $value > 0);
        $productRating = $ratedSources->isNotEmpty()
            ? round((float) $ratedSources->avg(), 2)
            : round((float) ($seller->rating ?? SellerReputationService::BASELINE_PUBLIC_RATING), 2);

        $soldProducts = (int) DB::table('seller_order_items')
            ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
            ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
            ->where('seller_order_items.seller_id', $seller->id)
            ->whereNull('seller_order_items.cancelled_at')
            ->whereNotNull('solds.completed_at')
            ->where(function ($query) {
                $query->where('solds.payment_status_code', 'paid')
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('solds.payment_status_code')
                            ->where('solds.paymentStatus', 2);
                    });
            })
            ->sum('seller_order_items.quantity');

        $returnedProducts = (int) DB::table('seller_order_items')
            ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
            ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
            ->where('seller_order_items.seller_id', $seller->id)
            ->where(function ($query) {
                $query->where('solds.status_code', 'returned')
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('solds.status_code')
                            ->where('solds.status', 'R');
                    });
            })
            ->sum('seller_order_items.quantity');

        $approvedSales = (int) SellerTransaction::query()
            ->where('seller_id', $seller->id)
            ->where('status', SellerTransaction::STATUS_APPROVED)
            ->where('category', 'order_sale')
            ->sum('netAmount');

        $approvedReversals = (int) SellerTransaction::query()
            ->where('seller_id', $seller->id)
            ->where('status', SellerTransaction::STATUS_APPROVED)
            ->where('category', 'order_reversal')
            ->sum('netAmount');

        $totalIncome = max(0, $approvedSales - $approvedReversals);
        $withdrawableBalance = max(0, (int) ($seller->balance ?? 0));
        $totalWithdrawal = max(0, (int) ($seller->total_withdrawal ?? 0));

        $productScore = round(max(45, min(100, ($productRating / 5) * 100)), 2);
        $responseHours = max(0.25, (float) ($metrics['response_time_hours'] ?? $seller->response_time_hours ?? 24));
        $responseScore = match (true) {
            $responseHours <= 1 => 100.0,
            $responseHours <= 3 => 95.0,
            $responseHours <= 6 => 90.0,
            $responseHours <= 12 => 82.0,
            $responseHours <= 24 => 72.0,
            $responseHours <= 48 => 58.0,
            default => 45.0,
        };
        $successScore = round(((float) ($metrics['success_rate'] ?? 0.78)) * 100, 2);
        $catalogHealth = ($bookCount + $stationeryCount) > 0
            ? round((($activeBooks + $activeStationery) / ($bookCount + $stationeryCount)) * 100, 2)
            : 70.0;

        $karma = round(
            (($reputation['reputation_score'] ?? $seller->reputation_score ?? 78) * 0.46) +
            ($productScore * 0.24) +
            ($responseScore * 0.18) +
            ($catalogHealth * 0.07) +
            ($successScore * 0.05),
            2
        );

        $insight = $this->buildInsight($karma, [
            'reputation_score' => (float) ($reputation['reputation_score'] ?? $seller->reputation_score ?? 78),
            'product_score' => $productScore,
            'response_score' => $responseScore,
            'catalog_health' => $catalogHealth,
            'success_score' => $successScore,
            'active_warning_count' => (int) ($metrics['active_warning_count'] ?? 0),
            'warning_weighted_impact' => (float) ($metrics['warning_weighted_impact'] ?? 0),
        ]);

        return [
            'karma' => $karma,
            'tier' => $insight['tier_en'],
            'karma_code' => $insight['code'],
            'karma_label_uz' => $insight['label_uz'],
            'karma_label_ru' => $insight['label_ru'],
            'karma_hint_uz' => $insight['hint_uz'],
            'karma_hint_ru' => $insight['hint_ru'],
            'karma_focus' => $insight['focus'],
            'public_rating' => round((float) ($reputation['rating'] ?? $seller->rating ?? 5), 2),
            'reputation_score' => round((float) ($reputation['reputation_score'] ?? $seller->reputation_score ?? 78), 2),
            'product_rating' => $productRating,
            'product_score' => $productScore,
            'response_time_hours' => round($responseHours, 2),
            'response_score' => $responseScore,
            'success_score' => $successScore,
            'catalog_health' => $catalogHealth,
            'active_warning_count' => (int) ($metrics['active_warning_count'] ?? 0),
            'warning_weighted_impact' => (float) ($metrics['warning_weighted_impact'] ?? 0),
            'completed_orders' => (int) ($metrics['completed_all'] ?? 0),
            'completed_30d' => (int) ($metrics['completed_30d'] ?? 0),
            'cancelled_90d' => (int) ($metrics['cancelled_90d'] ?? 0),
            'returned_90d' => (int) ($metrics['returned_90d'] ?? 0),
            'active_products' => $activeBooks + $activeStationery,
            'all_products' => $bookCount + $stationeryCount,
            'sold_products' => $soldProducts,
            'returned_products' => $returnedProducts,
            'total_income' => $totalIncome,
            'withdrawable_balance' => $withdrawableBalance,
            'total_withdrawal' => $totalWithdrawal,
        ];
    }

    private function buildInsight(float $karma, array $signals): array
    {
        $warningImpact = (float) ($signals['warning_weighted_impact'] ?? 0);
        $warningFocusScore = $warningImpact > 0
            ? max(0.0, 100 - min(100, ($warningImpact / 3) * 100))
            : 100.0;

        $focus = collect([
            ['key' => 'warning', 'score' => $warningFocusScore],
            ['key' => 'response', 'score' => (float) ($signals['response_score'] ?? 0)],
            ['key' => 'product', 'score' => (float) ($signals['product_score'] ?? 0)],
            ['key' => 'catalog', 'score' => (float) ($signals['catalog_health'] ?? 0)],
            ['key' => 'success', 'score' => (float) ($signals['success_score'] ?? 0)],
            ['key' => 'service', 'score' => (float) ($signals['reputation_score'] ?? 0)],
        ])->when(
            (int) ($signals['active_warning_count'] ?? 0) <= 0,
            fn ($collection) => $collection->reject(fn ($item) => $item['key'] === 'warning')
        )->sortBy('score')->values();

        $primaryFocus = (string) ($focus->first()['key'] ?? 'service');
        $focusKeys = $focus->take(2)->pluck('key')->all();

        [$labelUz, $labelRu, $tierEn, $code] = match (true) {
            $karma >= 92 => ["A'lo", 'Отлично', 'Top store', 'elite'],
            $karma >= 82 => ['Yaxshi', 'Хорошо', 'Strong store', 'strong'],
            $karma >= 68 => ['Barqaror', 'Стабильно', 'Stable store', 'stable'],
            $karma >= 52 => ["O'smoqda", 'Растет', 'Growing store', 'growing'],
            default => ['Diqqat kerak', 'Нужен фокус', 'Needs focus', 'attention'],
        };

        $shortUz = $this->focusShortUz($primaryFocus);
        $shortRu = $this->focusShortRu($primaryFocus);
        $longUz = $this->focusLongUz($primaryFocus);
        $longRu = $this->focusLongRu($primaryFocus);

        [$hintUz, $hintRu] = match (true) {
            $karma >= 92 => [
                "Siz barcha do'konlar ichida eng yuqori o'rinlarga yaqin turibsiz. Hozirgi tezlik va xizmat sifatini shu darajada ushlab turing.",
                'Вы уже близки к числу самых сильных магазинов. Сохраняйте текущий темп и качество сервиса.',
            ],
            $karma >= 82 => [
                "Do'kon juda yaxshi ishlayapti. Yana {$shortUz} qilsangiz eng yuqori toifaga chiqishingiz osonlashadi.",
                "Магазин работает очень уверенно. Если еще немного {$shortRu}, вы быстро выйдете в топ-категорию.",
            ],
            $karma >= 68 => [
                "Natija barqaror. Keyingi bosqich uchun {$shortUz} tavsiya qilinadi.",
                "Результат стабильный. Чтобы расти дальше, стоит {$shortRu}.",
            ],
            $karma >= 52 => [
                "Do'kon o'sish bosqichida. Avvalo {$longUz}",
                "Магазин в стадии роста. В первую очередь стоит {$longRu}",
            ],
            default => [
                "Foizni ko'tarish uchun birinchi navbatda {$longUz}",
                "Чтобы поднять процент, в первую очередь нужно {$longRu}",
            ],
        };

        return [
            'code' => $code,
            'tier_en' => $tierEn,
            'label_uz' => $labelUz,
            'label_ru' => $labelRu,
            'hint_uz' => $hintUz,
            'hint_ru' => $hintRu,
            'focus' => $focusKeys,
        ];
    }

    private function focusShortUz(string $key): string
    {
        return match ($key) {
            'warning' => "qoidabuzarliklarni kamaytirib, yangi ogohlantirish olmasangiz",
            'response' => "javob vaqtini qisqartirsangiz",
            'product' => "mahsulot sifati va ta'riflarini kuchaytirsangiz",
            'catalog' => "faol katalog ulushini oshirsangiz",
            'success' => "qaytgan va bekor buyurtmalarni kamaytirsangiz",
            default => "xizmat intizomini yanada barqaror qilsangiz",
        };
    }

    private function focusShortRu(string $key): string
    {
        return match ($key) {
            'warning' => 'избежать новых предупреждений и снизить нарушения',
            'response' => 'ускорить ответы клиентам',
            'product' => 'усилить качество и описание товаров',
            'catalog' => 'увеличить долю активного каталога',
            'success' => 'снизить возвраты и отмены',
            default => 'сделать сервис еще стабильнее',
        };
    }

    private function focusLongUz(string $key): string
    {
        return match ($key) {
            'warning' => "yangi ogohlantirish olmaslik, mavjud kamchiliklarni tez yopish va qoidabuzarliklarni takrorlamaslik kerak.",
            'response' => "mijozlarga tezroq javob bering va suhbatlarni javobsiz qoldirmang.",
            'product' => "mahsulot sifati, ta'rifi va baholangan tovarlar ulushini yaxshilang.",
            'catalog' => "faol va sotuvga tayyor mahsulotlar ulushini oshiring, stocksiz pozitsiyalarni tartibga soling.",
            'success' => "bekor va qaytgan buyurtmalarni kamaytirib, muvaffaqiyatli yakunlangan buyurtmalar ulushini oshiring.",
            default => "javob, buyurtma nazorati va mijoz tajribasini bir xil barqaror darajada ushlang.",
        };
    }

    private function focusLongRu(string $key): string
    {
        return match ($key) {
            'warning' => 'не получать новые предупреждения, быстро закрыть текущие замечания и не повторять нарушения.',
            'response' => 'быстрее отвечать клиентам и не оставлять диалоги без ответа.',
            'product' => 'улучшить качество товаров, описания и долю хорошо оцененных позиций.',
            'catalog' => 'увеличить долю активных товаров и навести порядок в позициях без остатка.',
            'success' => 'снизить возвраты и отмены, повысив долю успешно завершенных заказов.',
            default => 'держать ответы, контроль заказов и клиентский опыт на одинаково стабильном уровне.',
        };
    }
}
