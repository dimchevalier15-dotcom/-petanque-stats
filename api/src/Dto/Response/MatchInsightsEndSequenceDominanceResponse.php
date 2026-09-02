<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightsEndSequenceDominanceResponse
{
    public function __construct(
        public int $endsDominated,
        public int $endsWonWhileDominating,
        public int $pointsOnDominatedEnds,
        public int $totalPointsScored,
    ) {
    }
}
