<?php

declare(strict_types=1);

namespace App\Tests\Service\Shooting;

use App\Enum\ShootingShotResult;
use App\Enum\ShootingWorkshop;
use App\Service\Shooting\ShootingScoreCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ShootingScoreCalculatorTest extends TestCase
{
    private ShootingScoreCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ShootingScoreCalculator();
    }

    #[DataProvider('standardWorkshopProvider')]
    public function testStandardWorkshopsUseTheZeroOneThreeFiveScale(ShootingWorkshop $workshop): void
    {
        self::assertSame(0, $this->calculator->pointsFor($workshop, ShootingShotResult::MISSED));
        self::assertSame(1, $this->calculator->pointsFor($workshop, ShootingShotResult::TOUCHED));
        self::assertSame(3, $this->calculator->pointsFor($workshop, ShootingShotResult::SUCCESSFUL));
        self::assertSame(5, $this->calculator->pointsFor($workshop, ShootingShotResult::CARREAU));
    }

    /**
     * @return list<array{0:ShootingWorkshop}>
     */
    public static function standardWorkshopProvider(): array
    {
        return [
            [ShootingWorkshop::BALL_ALONE],
            [ShootingWorkshop::BALL_BEHIND_JACK],
            [ShootingWorkshop::BETWEEN_TWO_BALLS],
            [ShootingWorkshop::JUMPED_BALL],
        ];
    }

    public function testJackWorkshopHasItsOwnScaleWithoutCarreau(): void
    {
        self::assertSame(0, $this->calculator->pointsFor(ShootingWorkshop::JACK, ShootingShotResult::MISSED));
        self::assertSame(3, $this->calculator->pointsFor(ShootingWorkshop::JACK, ShootingShotResult::TOUCHED));
        self::assertSame(5, $this->calculator->pointsFor(ShootingWorkshop::JACK, ShootingShotResult::SUCCESSFUL));
    }

    public function testCarreauIsNotAllowedOnTheJackWorkshop(): void
    {
        self::assertFalse($this->calculator->isResultAllowedForWorkshop(ShootingWorkshop::JACK, ShootingShotResult::CARREAU));

        $this->expectException(InvalidArgumentException::class);
        $this->calculator->pointsFor(ShootingWorkshop::JACK, ShootingShotResult::CARREAU);
    }

    public function testMaximumPossibleScoreForAFullSessionIsOneHundred(): void
    {
        $total = 0;
        foreach (ShootingWorkshop::all() as $workshop) {
            $best = $workshop === ShootingWorkshop::JACK ? ShootingShotResult::SUCCESSFUL : ShootingShotResult::CARREAU;
            $total += 4 * $this->calculator->pointsFor($workshop, $best);
        }

        self::assertSame(100, $total);
    }
}
