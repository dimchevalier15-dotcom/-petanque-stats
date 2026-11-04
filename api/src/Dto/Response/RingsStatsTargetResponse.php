<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class RingsStatsTargetResponse
{
    public function __construct(
        public string $target,
        public int $attemptCount,
        public int $successCount,
        public float $successRate,
        public int $carreauCount,
        public float $averagePointsPerAttempt,
    ) {
    }
}
