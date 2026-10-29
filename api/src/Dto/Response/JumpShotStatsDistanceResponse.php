<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class JumpShotStatsDistanceResponse
{
    public function __construct(
        public int $distance,
        public int $throwCount,
        public float $successRate,
    ) {
    }
}
