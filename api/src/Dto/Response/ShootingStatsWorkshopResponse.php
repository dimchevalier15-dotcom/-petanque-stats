<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ShootingStatsWorkshopResponse
{
    public function __construct(
        public int $workshop,
        public int $shotCount,
        public float $averageScore,
    ) {
    }
}
