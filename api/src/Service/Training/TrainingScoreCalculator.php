<?php

declare(strict_types=1);

namespace App\Service\Training;

use App\Enum\TrainingPointResult;
use App\Enum\TrainingTirResult;
use App\Enum\TrainingType;

final class TrainingScoreCalculator
{
    public function scoreFor(TrainingType $type, string $result): int
    {
        if ($type === TrainingType::POINT) {
            return match ($result) {
                TrainingPointResult::PERFECT->value => 2,
                TrainingPointResult::VERY_GOOD->value => 1,
                TrainingPointResult::GOOD->value => 1,
                TrainingPointResult::ACCEPTABLE->value => 0,
                TrainingPointResult::BAD->value => -1,
                TrainingPointResult::USELESS->value => -2,
                default => 0,
            };
        }

        return match ($result) {
            TrainingTirResult::CARREAU->value => 3,
            TrainingTirResult::PALET->value => 2,
            TrainingTirResult::SUCCESSFUL->value => 1,
            default => 0,
        };
    }

    public function isResultAllowed(TrainingType $type, string $result): bool
    {
        if ($type === TrainingType::POINT) {
            return TrainingPointResult::tryFrom($result) !== null;
        }

        return TrainingTirResult::tryFrom($result) !== null;
    }

    public function isSuccessful(TrainingType $type, string $result): bool
    {
        if ($type === TrainingType::POINT) {
            return in_array($result, TrainingPointResult::successfulValues(), true);
        }

        return in_array($result, TrainingTirResult::successfulValues(), true);
    }
}
