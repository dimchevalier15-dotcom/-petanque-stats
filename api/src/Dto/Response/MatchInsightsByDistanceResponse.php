<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightsByDistanceResponse
{
    public function __construct(
        public string $bucket,
        public ?MatchInsightsDistanceTeamResponse $teamA,
        public ?MatchInsightsDistanceTeamResponse $teamB,
        public ?string $dominantTeam,
    ) {
    }
}
