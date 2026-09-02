<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightsResponse
{
    public function __construct(
        public string $status,
        public ?string $reason = null,
        public ?MatchInsightsTeamResponse $teamA = null,
        public ?MatchInsightsTeamResponse $teamB = null,
        public ?MatchInsightsMarkingTeamResponse $markingTeamA = null,
        public ?MatchInsightsMarkingTeamResponse $markingTeamB = null,
        public ?MatchInsightsRajoutTeamResponse $rajoutTeamA = null,
        public ?MatchInsightsRajoutTeamResponse $rajoutTeamB = null,
        public ?MatchInsightsHeldEndErrorResponse $heldEndErrorTeamA = null,
        public ?MatchInsightsHeldEndErrorResponse $heldEndErrorTeamB = null,
        public ?MatchInsightsPointDominanceResponse $pointDominanceTeamA = null,
        public ?MatchInsightsPointDominanceResponse $pointDominanceTeamB = null,
        public ?MatchInsightsEndSequenceDominanceResponse $endSequenceDominanceTeamA = null,
        public ?MatchInsightsEndSequenceDominanceResponse $endSequenceDominanceTeamB = null,
        public ?MatchInsightsDistanceOutlookResponse $distanceOutlook = null,
        public ?MatchInsightsCoverageResponse $coverage = null,
    ) {
    }
}
