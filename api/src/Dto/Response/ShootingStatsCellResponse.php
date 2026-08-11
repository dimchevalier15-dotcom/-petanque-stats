<?php

declare(strict_types=1);

namespace App\Dto\Response;

/** One cell in the workshop × distance performance grid. */
final class ShootingStatsCellResponse
{
    public function __construct(
        public int $workshop,
        public int $distance,
        public int $shotCount,
        public float $averageScore,
    ) {
    }
}
