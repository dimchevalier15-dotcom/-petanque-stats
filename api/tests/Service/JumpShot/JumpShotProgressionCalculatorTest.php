<?php

declare(strict_types=1);

namespace App\Tests\Service\JumpShot;

use App\Enum\JumpShotSeriesConsequence;
use App\Enum\JumpShotThrowResult;
use App\Enum\ShootingDistance;
use App\Service\JumpShot\JumpShotProgressionCalculator;
use PHPUnit\Framework\TestCase;

final class JumpShotProgressionCalculatorTest extends TestCase
{
    private JumpShotProgressionCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new JumpShotProgressionCalculator();
    }

    public function testZeroSuccessesMoveDown(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::SEVEN_METERS,
            ShootingDistance::SEVEN_METERS,
            $this->results(JumpShotThrowResult::MISSED, JumpShotThrowResult::MISSED, JumpShotThrowResult::MISSED),
        );

        self::assertSame(0, $eval->successCount);
        self::assertSame(JumpShotSeriesConsequence::DOWN, $eval->consequence);
        self::assertSame(ShootingDistance::SIX_METERS, $eval->nextDistance);
    }

    public function testOneSuccessMovesDown(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::EIGHT_METERS,
            ShootingDistance::EIGHT_METERS,
            $this->results(JumpShotThrowResult::CHIQUE, JumpShotThrowResult::MISSED, JumpShotThrowResult::MISSED),
        );

        self::assertSame(1, $eval->successCount);
        self::assertSame(JumpShotSeriesConsequence::DOWN, $eval->consequence);
        self::assertSame(ShootingDistance::SEVEN_METERS, $eval->nextDistance);
    }

    public function testTwoSuccessesStay(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::SEVEN_METERS,
            ShootingDistance::SEVEN_METERS,
            $this->results(JumpShotThrowResult::HIT, JumpShotThrowResult::CARREAU, JumpShotThrowResult::MISSED),
        );

        self::assertSame(2, $eval->successCount);
        self::assertSame(JumpShotSeriesConsequence::STAY, $eval->consequence);
        self::assertSame(ShootingDistance::SEVEN_METERS, $eval->nextDistance);
    }

    public function testThreeSuccessesMoveUp(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::SIX_METERS,
            ShootingDistance::SIX_METERS,
            $this->results(JumpShotThrowResult::CHIQUE, JumpShotThrowResult::HIT, JumpShotThrowResult::CARREAU),
        );

        self::assertSame(3, $eval->successCount);
        self::assertSame(JumpShotSeriesConsequence::UP, $eval->consequence);
        self::assertSame(ShootingDistance::SEVEN_METERS, $eval->nextDistance);
        self::assertFalse($eval->sessionWon);
    }

    public function testCannotGoBelowSixMeters(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::SIX_METERS,
            ShootingDistance::SIX_METERS,
            $this->results(JumpShotThrowResult::MISSED, JumpShotThrowResult::MISSED, JumpShotThrowResult::MISSED),
        );

        self::assertSame(ShootingDistance::SIX_METERS, $eval->nextDistance);
    }

    public function testCannotGoAboveNineMeters(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::NINE_METERS,
            ShootingDistance::NINE_METERS,
            $this->results(JumpShotThrowResult::CHIQUE, JumpShotThrowResult::HIT, JumpShotThrowResult::MISSED),
        );

        self::assertSame(JumpShotSeriesConsequence::STAY, $eval->consequence);
        self::assertSame(ShootingDistance::NINE_METERS, $eval->nextDistance);
    }

    public function testCarreauSalvateur(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::EIGHT_METERS,
            ShootingDistance::EIGHT_METERS,
            $this->results(JumpShotThrowResult::MISSED, JumpShotThrowResult::MISSED, JumpShotThrowResult::CARREAU),
        );

        self::assertTrue($eval->isSpecialSeries);
        self::assertSame(1, $eval->successCount);
        self::assertSame(JumpShotSeriesConsequence::STAY, $eval->consequence);
        self::assertSame(ShootingDistance::EIGHT_METERS, $eval->nextDistance);
    }

    public function testTwoMissedThenChiqueIsNormalDescent(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::SEVEN_METERS,
            ShootingDistance::SEVEN_METERS,
            $this->results(JumpShotThrowResult::MISSED, JumpShotThrowResult::MISSED, JumpShotThrowResult::CHIQUE),
        );

        self::assertFalse($eval->isSpecialSeries);
        self::assertSame(1, $eval->successCount);
        self::assertSame(JumpShotSeriesConsequence::DOWN, $eval->consequence);
    }

    public function testTwoMissedThenHitIsNormalDescent(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::SEVEN_METERS,
            ShootingDistance::SEVEN_METERS,
            $this->results(JumpShotThrowResult::MISSED, JumpShotThrowResult::MISSED, JumpShotThrowResult::HIT),
        );

        self::assertFalse($eval->isSpecialSeries);
        self::assertSame(JumpShotSeriesConsequence::DOWN, $eval->consequence);
    }

    public function testVictoryAtNineWithThreeSuccesses(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::NINE_METERS,
            ShootingDistance::NINE_METERS,
            $this->results(JumpShotThrowResult::CHIQUE, JumpShotThrowResult::HIT, JumpShotThrowResult::CARREAU),
        );

        self::assertTrue($eval->sessionWon);
    }

    public function testTwoOfThreeAtNineIsNotVictory(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::NINE_METERS,
            ShootingDistance::NINE_METERS,
            $this->results(JumpShotThrowResult::CHIQUE, JumpShotThrowResult::HIT, JumpShotThrowResult::MISSED),
        );

        self::assertFalse($eval->sessionWon);
    }

    public function testThreeOfThreeAtSixIsNotVictory(): void
    {
        $eval = $this->calculator->evaluate(
            ShootingDistance::SIX_METERS,
            ShootingDistance::SIX_METERS,
            $this->results(JumpShotThrowResult::CHIQUE, JumpShotThrowResult::HIT, JumpShotThrowResult::CARREAU),
        );

        self::assertFalse($eval->sessionWon);
    }

    /**
     * @return array<int, JumpShotThrowResult>
     */
    private function results(JumpShotThrowResult $a, JumpShotThrowResult $b, JumpShotThrowResult $c): array
    {
        return [1 => $a, 2 => $b, 3 => $c];
    }
}
