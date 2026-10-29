<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PointingStatsWorkshopResponse
{
    public function __construct(
        public int $workshop,
        public int $shotCount,
        public float $averageScore,
    ) {
    }
}
