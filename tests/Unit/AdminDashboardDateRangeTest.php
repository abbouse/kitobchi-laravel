<?php

namespace Tests\Unit;

use App\Http\Controllers\Boshqaruv\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class AdminDashboardDateRangeTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function year_period_uses_the_same_elapsed_window_for_comparison(): void
    {
        Carbon::setTestNow('2026-07-13 16:30:00');

        $range = $this->resolveRange(['dashboard_period' => 'year']);

        $this->assertSame('year', $range['key']);
        $this->assertSame('2026-01-01 00:00:00', $range['from']->toDateTimeString());
        $this->assertSame('2025-01-01 00:00:00', $range['previousFrom']->toDateTimeString());
        $this->assertSame(
            $range['from']->diffInSeconds($range['to']),
            $range['previousFrom']->diffInSeconds($range['previousTo']),
        );
    }

    #[Test]
    public function custom_period_normalizes_reversed_dates_and_includes_the_last_day(): void
    {
        Carbon::setTestNow('2026-07-13 16:30:00');

        $range = $this->resolveRange([
            'dashboard_period' => 'custom',
            'dashboard_from' => '2026-07-10',
            'dashboard_to' => '2026-07-03',
        ]);

        $this->assertSame('2026-07-03', $range['from']->toDateString());
        $this->assertSame('2026-07-10', $range['displayTo']->toDateString());
        $this->assertSame('2026-07-11 00:00:00', $range['to']->toDateTimeString());
        $this->assertSame('25.06.2026 - 02.07.2026', $range['previousFrom']->format('d.m.Y').' - '.$range['previousTo']->copy()->subSecond()->format('d.m.Y'));
    }

    #[Test]
    public function all_time_period_has_no_artificial_previous_window(): void
    {
        Carbon::setTestNow('2026-07-13 16:30:00');

        $range = $this->resolveRange(['dashboard_period' => 'all']);

        $this->assertNull($range['from']);
        $this->assertNull($range['previousFrom']);
        $this->assertNull($range['previousTo']);
        $this->assertSame('Barcha vaqt', $range['label']);
    }

    private function resolveRange(array $query): array
    {
        $method = new ReflectionMethod(AdminController::class, 'dashboardDateRange');

        return $method->invoke(new AdminController, Request::create('/boshqaruv', 'GET', $query));
    }
}
