<?php

declare(strict_types=1);

namespace App\Service\JumpShot;

use App\Enum\JumpShotProgressionMode;
use App\Enum\JumpShotSeriesConsequence;
use App\Enum\JumpShotThrowResult;
use App\Enum\ShootingDistance;

final class JumpShotSeriesEvaluation
{
    public function __construct(
        public readonly int $successCount,
        public readonly bool $isSpecialSeries,
        public readonly JumpShotSeriesConsequence $consequence,
        public readonly ShootingDistance $nextDistance,
        public readonly bool $sessionWon,
    ) {
    }
}

final class JumpShotProgressionCalculator
{
    /**
     * @param array<int, JumpShotThrowResult> $resultsByPosition keys 1, 2, 3
     */
    public function evaluate(
        ShootingDistance $seriesDistance,
        ShootingDistance $sessionCurrentDistance,
        array $resultsByPosition,
        JumpShotProgressionMode $progressionMode = JumpShotProgressionMode::STANDARD,
    ): JumpShotSeriesEvaluation {
        $first = $resultsByPosition[1];
        $second = $resultsByPosition[2];
        $third = $resultsByPosition[3];

        $rawSuccessCount = 0;
        foreach ($resultsByPosition as $result) {
            if ($result->isSuccess()) {
                ++$rawSuccessCount;
            }
        }

        $isSpecialSeries = $progressionMode === JumpShotProgressionMode::STANDARD
            && $first === JumpShotThrowResult::MISSED
            && $second === JumpShotThrowResult::MISSED
            && $third === JumpShotThrowResult::CARREAU;

        if ($isSpecialSeries) {
            $successCount = 1;
            $consequence = JumpShotSeriesConsequence::STAY;
        } else {
            $successCount = $rawSuccessCount;
            $consequence = $this->consequenceForSuccessCount($successCount, $progressionMode);
        }

        $sessionWon = $seriesDistance === ShootingDistance::NINE_METERS
            && ($progressionMode === JumpShotProgressionMode::SIMPLIFIED
                ? $rawSuccessCount >= 2
                : $rawSuccessCount === 3);
        $nextDistance = $sessionWon
            ? $sessionCurrentDistance
            : $this->applyConsequence($sessionCurrentDistance, $consequence);

        return new JumpShotSeriesEvaluation(
            $successCount,
            $isSpecialSeries,
            $consequence,
            $nextDistance,
            $sessionWon,
        );
    }

    private function consequenceForSuccessCount(
        int $successCount,
        JumpShotProgressionMode $progressionMode,
    ): JumpShotSeriesConsequence {
        if ($progressionMode === JumpShotProgressionMode::SIMPLIFIED) {
            return match ($successCount) {
                0 => JumpShotSeriesConsequence::DOWN,
                1 => JumpShotSeriesConsequence::STAY,
                default => JumpShotSeriesConsequence::UP,
            };
        }

        return match ($successCount) {
            0, 1 => JumpShotSeriesConsequence::DOWN,
            2 => JumpShotSeriesConsequence::STAY,
            3 => JumpShotSeriesConsequence::UP,
        };
    }

    private function applyConsequence(
        ShootingDistance $current,
        JumpShotSeriesConsequence $consequence,
    ): ShootingDistance {
        $order = ShootingDistance::all();
        $index = array_search($current, $order, true);
        if ($index === false) {
            return $current;
        }

        return match ($consequence) {
            JumpShotSeriesConsequence::DOWN => $order[max(0, $index - 1)],
            JumpShotSeriesConsequence::STAY => $current,
            JumpShotSeriesConsequence::UP => $order[min(\count($order) - 1, $index + 1)],
        };
    }
}
