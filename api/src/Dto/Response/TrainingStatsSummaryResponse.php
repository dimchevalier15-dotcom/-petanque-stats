<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TrainingStatsSummaryResponse
{
    public function __construct(
        public int $sessionsCount,
        public int $totalBalls,
        public int $successfulBalls,
        public ?float $successRate,
        public ?int $bestScore,
        public ?float $averageScore,
    ) {
    }
}
