<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TrainingSessionStartedResponse
{
    public function __construct(
        public int $id,
        public string $type,
        public float $distance,
        public int $plannedBalls,
        public string $createdAt,
        public int $attemptsCount,
        public int $currentScore,
    ) {
    }
}
