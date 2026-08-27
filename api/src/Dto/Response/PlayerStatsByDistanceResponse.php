<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerStatsByDistanceResponse
{
    public function __construct(
        public string $bucket,
        public int $ballCount,
        public float $average,
        public int $p2,
        public int $p1,
        public int $p0,
        public int $m1,
        public int $m2,
    ) {
    }
}
