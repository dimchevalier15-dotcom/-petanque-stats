<?php

declare(strict_types=1);

namespace App\Tests\Service\Training;

use App\Enum\TrainingPointResult;
use App\Enum\TrainingTirResult;
use App\Enum\TrainingType;
use App\Service\Training\TrainingScoreCalculator;
use PHPUnit\Framework\TestCase;

final class TrainingScoreCalculatorTest extends TestCase
{
    private TrainingScoreCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new TrainingScoreCalculator();
    }

    public function testPointScoring(): void
    {
        self::assertSame(2, $this->calculator->scoreFor(TrainingType::POINT, TrainingPointResult::PERFECT->value));
        self::assertSame(1, $this->calculator->scoreFor(TrainingType::POINT, TrainingPointResult::VERY_GOOD->value));
        self::assertSame(1, $this->calculator->scoreFor(TrainingType::POINT, TrainingPointResult::GOOD->value));
        self::assertSame(0, $this->calculator->scoreFor(TrainingType::POINT, TrainingPointResult::ACCEPTABLE->value));
        self::assertSame(-1, $this->calculator->scoreFor(TrainingType::POINT, TrainingPointResult::BAD->value));
        self::assertSame(-2, $this->calculator->scoreFor(TrainingType::POINT, TrainingPointResult::USELESS->value));
    }

    public function testTirScoring(): void
    {
        self::assertSame(0, $this->calculator->scoreFor(TrainingType::TIR, TrainingTirResult::MISSED->value));
        self::assertSame(0, $this->calculator->scoreFor(TrainingType::TIR, TrainingTirResult::TOUCHED->value));
        self::assertSame(1, $this->calculator->scoreFor(TrainingType::TIR, TrainingTirResult::SUCCESSFUL->value));
        self::assertSame(2, $this->calculator->scoreFor(TrainingType::TIR, TrainingTirResult::PALET->value));
        self::assertSame(3, $this->calculator->scoreFor(TrainingType::TIR, TrainingTirResult::CARREAU->value));
    }

    public function testSuccessDetection(): void
    {
        self::assertTrue($this->calculator->isSuccessful(TrainingType::POINT, TrainingPointResult::PERFECT->value));
        self::assertTrue($this->calculator->isSuccessful(TrainingType::POINT, TrainingPointResult::GOOD->value));
        self::assertFalse($this->calculator->isSuccessful(TrainingType::POINT, TrainingPointResult::BAD->value));
        self::assertTrue($this->calculator->isSuccessful(TrainingType::TIR, TrainingTirResult::SUCCESSFUL->value));
        self::assertTrue($this->calculator->isSuccessful(TrainingType::TIR, TrainingTirResult::PALET->value));
        self::assertTrue($this->calculator->isSuccessful(TrainingType::TIR, TrainingTirResult::CARREAU->value));
        self::assertFalse($this->calculator->isSuccessful(TrainingType::TIR, TrainingTirResult::MISSED->value));
    }
}
