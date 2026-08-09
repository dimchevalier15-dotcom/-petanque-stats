<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerStatsSummaryResponse
{
    public function __construct(
        public int $matchesPlayed,
        public int $victories,
        public int $defeats,
        public ?float $winRate,
        public int $trackedMatches,
        public int $totalBalls,
    ) {
    }
}
