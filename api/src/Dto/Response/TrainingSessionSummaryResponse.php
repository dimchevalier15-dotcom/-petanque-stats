<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TrainingSessionSummaryResponse
{
    /** @param list<TrainingAttemptSummary> $attempts */
    public function __construct(
        public int $id,
        public string $type,
        public float $distance,
        public int $plannedBalls,
        public string $createdAt,
        public ?string $finishedAt,
        public ?int $totalScore,
        public int $successfulBalls,
        public ?float $successRate,
        public array $attempts,
    ) {
    }
}
