<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ShootingStatsDistanceResponse
{
    public function __construct(
        public int $distance,
        public int $shotCount,
        public float $averageScore,
    ) {
    }
}
