<?php

declare(strict_types=1);

namespace App\Tests\Service\Pointing;

use App\Enum\PointingShotResult;
use App\Enum\PointingWorkshop;
use App\Service\Pointing\PointingScoreCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PointingScoreCalculatorTest extends TestCase
{
    private PointingScoreCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PointingScoreCalculator();
    }

    #[DataProvider('workshopProvider')]
    public function testEveryWorkshopUsesTheZeroThreeNineFifteenScale(PointingWorkshop $workshop): void
    {
        self::assertSame(0, $this->calculator->pointsFor($workshop, PointingShotResult::MISSED));
        self::assertSame(3, $this->calculator->pointsFor($workshop, PointingShotResult::P3));
        self::assertSame(9, $this->calculator->pointsFor($workshop, PointingShotResult::P9));
        self::assertSame(15, $this->calculator->pointsFor($workshop, PointingShotResult::P15));
    }

    /**
     * @return list<array{0:PointingWorkshop}>
     */
    public static function workshopProvider(): array
    {
        return array_map(static fn (PointingWorkshop $w) => [$w], PointingWorkshop::all());
    }

    public function testMaximumPossibleScoreForAFullSessionIsThreeHundred(): void
    {
        $total = 0;
        foreach (PointingWorkshop::all() as $workshop) {
            $total += 4 * $this->calculator->pointsFor($workshop, PointingShotResult::P15);
        }

        self::assertSame(300, $total);
    }
}
