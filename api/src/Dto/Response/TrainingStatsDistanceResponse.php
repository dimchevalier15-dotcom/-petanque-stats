<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TrainingStatsDistanceResponse
{
    public function __construct(
        public float $distance,
        public int $ballCount,
        public float $successRate,
        public float $averageScore,
    ) {
    }
}
