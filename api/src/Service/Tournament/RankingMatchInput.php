<?php

declare(strict_types=1);

namespace App\Service\Tournament;

final class RankingMatchInput
{
    public function __construct(
        public int $team1Id,
        public int $team2Id,
        public int $score1,
        public int $score2,
    ) {
    }
}
