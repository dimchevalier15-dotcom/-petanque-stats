<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightsTeamResponse
{
    public function __construct(
        public string $team,
        public int $endsWon,
        public int $endsOpened,
        public float $firstShotAverage,
        public int $capitalizedCount,
        public int $capitalizationOpportunities,
        public float $avgPointsWhenCapitalizing,
        public int $defendedCount,
        public int $defenseSituations,
        public float $avgPointsConcededWhenDefending,
        public int $reclaimsCount,
    ) {
    }
}
