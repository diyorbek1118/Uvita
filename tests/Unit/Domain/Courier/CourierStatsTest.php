<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Courier;

use Modules\Courier\Domain\ValueObjects\CourierStats;
use PHPUnit\Framework\TestCase;

class CourierStatsTest extends TestCase
{
    public function test_success_rate_is_calculated_correctly(): void
    {
        $stats = new CourierStats(
            totalDelivered: 80,
            totalNotFound:  20,
            totalActive:    3,
        );

        $this->assertSame(80.0, $stats->successRate);
    }

    public function test_success_rate_is_zero_when_no_deliveries(): void
    {
        $stats = new CourierStats(
            totalDelivered: 0,
            totalNotFound:  0,
            totalActive:    0,
        );

        $this->assertSame(0.0, $stats->successRate);
    }

    public function test_success_rate_is_100_when_no_not_found(): void
    {
        $stats = new CourierStats(
            totalDelivered: 50,
            totalNotFound:  0,
            totalActive:    2,
        );

        $this->assertSame(100.0, $stats->successRate);
    }

    public function test_success_rate_is_zero_when_all_not_found(): void
    {
        $stats = new CourierStats(
            totalDelivered: 0,
            totalNotFound:  10,
            totalActive:    0,
        );

        $this->assertSame(0.0, $stats->successRate);
    }

    public function test_success_rate_is_rounded_to_one_decimal(): void
    {
        $stats = new CourierStats(
            totalDelivered: 1,
            totalNotFound:  2,
            totalActive:    1,
        );

        // 1/3 * 100 = 33.333... → 33.3
        $this->assertSame(33.3, $stats->successRate);
    }

    public function test_all_counts_are_stored(): void
    {
        $stats = new CourierStats(
            totalDelivered: 10,
            totalNotFound:  2,
            totalActive:    3,
        );

        $this->assertSame(10, $stats->totalDelivered);
        $this->assertSame(2, $stats->totalNotFound);
        $this->assertSame(3, $stats->totalActive);
    }
}
