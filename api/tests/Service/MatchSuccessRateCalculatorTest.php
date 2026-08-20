<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\MatchSuccessRateCalculator;
use PHPUnit\Framework\TestCase;

final class MatchSuccessRateCalculatorTest extends TestCase
{
    private MatchSuccessRateCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new MatchSuccessRateCalculator();
    }

    public function testPlusTwoCountsAsSuccess(): void
    {
        self::assertSame(100.0, $this->calculator->fromNoteCounts(1, 0, 0, 0, 0));
    }

    public function testPlusOneCountsAsSuccess(): void
    {
        self::assertSame(100.0, $this->calculator->fromNoteCounts(0, 1, 0, 0, 0));
    }

    public function testZeroCountsAsFailure(): void
    {
        self::assertSame(0.0, $this->calculator->fromNoteCounts(0, 0, 1, 0, 0));
    }

    public function testMinusOneCountsAsFailure(): void
    {
        self::assertSame(0.0, $this->calculator->fromNoteCounts(0, 0, 0, 1, 0));
    }

    public function testMinusTwoCountsAsFailure(): void
    {
        self::assertSame(0.0, $this->calculator->fromNoteCounts(0, 0, 0, 0, 1));
    }

    public function testSixPositiveOutOfTenIsSixtyPercent(): void
    {
        self::assertSame(60.0, $this->calculator->fromNoteCounts(3, 3, 2, 1, 1));
    }

    public function testNoBallsReturnsNull(): void
    {
        self::assertNull($this->calculator->fromNoteCounts(0, 0, 0, 0, 0));
    }
}
