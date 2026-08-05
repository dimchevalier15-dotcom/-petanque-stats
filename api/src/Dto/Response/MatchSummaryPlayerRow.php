<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchSummaryPlayerRow
{
    public function __construct(
        public int $playerId,
        public string $firstName,
        public string $lastName,
        public string $nickname,
        public string $team, // 'A' | 'B'
        public float $average,
        public int $p2,
        public int $p1,
        public int $p0,
        public int $m1,
        public int $m2,
    ) {
    }
}

