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
        $hasCarreau = false;
        foreach ($resultsByPosition as $result) {
            if ($result === JumpShotThrowResult::CARREAU) {
                $hasCarreau = true;
            }
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
            $consequence = $this->consequenceForSuccessCount($successCount, $progressionMode, $hasCarreau);
        }

        $sessionWon = $seriesDistance === ShootingDistance::NINE_METERS
            && $this->isVictorySeries($rawSuccessCount, $hasCarreau, $progressionMode);
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
        bool $hasCarreau,
    ): JumpShotSeriesConsequence {
        return match ($progressionMode) {
            JumpShotProgressionMode::EASY => match ($successCount) {
                0, 1 => JumpShotSeriesConsequence::STAY,
                default => JumpShotSeriesConsequence::UP,
            },
            JumpShotProgressionMode::STANDARD => match ($successCount) {
                0 => JumpShotSeriesConsequence::DOWN,
                3 => JumpShotSeriesConsequence::UP,
                default => JumpShotSeriesConsequence::STAY,
            },
            JumpShotProgressionMode::HARD => match (true) {
                $successCount <= 1 => JumpShotSeriesConsequence::DOWN,
                $successCount === 3 && $hasCarreau => JumpShotSeriesConsequence::UP,
                default => JumpShotSeriesConsequence::STAY,
            },
        };
    }

    private function isVictorySeries(
        int $rawSuccessCount,
        bool $hasCarreau,
        JumpShotProgressionMode $progressionMode,
    ): bool {
        return match ($progressionMode) {
            JumpShotProgressionMode::EASY => $rawSuccessCount >= 2,
            JumpShotProgressionMode::STANDARD => $rawSuccessCount === 3,
            JumpShotProgressionMode::HARD => $rawSuccessCount === 3 && $hasCarreau,
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
