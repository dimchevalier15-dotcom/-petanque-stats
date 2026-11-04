<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class RingsStatsScoreDecileResponse
{
    public function __construct(
        public int $decile,
        public int $minScore,
        public int $maxScore,
        public int $sessionCount,
    ) {
    }
}
