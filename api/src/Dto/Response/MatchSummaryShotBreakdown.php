<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchSummaryShotBreakdown
{
    public function __construct(
        public float $average,
        public int $p2,
        public int $p1,
        public int $p0,
        public int $m1,
        public int $m2,
    ) {
    }
}
