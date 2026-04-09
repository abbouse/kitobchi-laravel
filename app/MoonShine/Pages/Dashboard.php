<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use Illuminate\Support\Facades\Cache;
use App\Models\BookCategories;
use App\Models\Books;
use App\Models\Couriers;
use App\Models\DeliveryService;
use App\Models\Gifts;
use App\Models\MarketNews;
use App\Models\Promocode;
use App\Models\Sold;
use App\Models\User;

// ✅ MoonShine 3.x uchun TO'G'RI importlar
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;

// ✅ moonshine/apexcharts paketi uchun TO'G'RI importlar
use MoonShine\Apexcharts\Components\DonutChartMetric;
use MoonShine\Apexcharts\Components\LineChartMetric;

class Dashboard extends Page
{
    private const CACHE_DURATION = 300; // 5 daqiqa kesh

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle()
        ];
    }

    public function getTitle(): string
    {
        return $this->title ?: 'Boshqaruv qismi';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        return ['test'];
    }
}