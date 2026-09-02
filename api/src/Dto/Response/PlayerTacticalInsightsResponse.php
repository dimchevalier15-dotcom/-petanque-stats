<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerTacticalInsightsResponse
{
    /**
     * @param list<PlayerTacticalInsightsByDistanceResponse> $markingByDistance
     * @param list<PlayerTacticalInsightsByDistanceResponse> $rajoutByDistance
     */
    public function __construct(
        public string $status,
        public ?string $reason = null,
        public ?MatchInsightsMarkingTeamResponse $markingOverall = null,
        public ?MatchInsightsRajoutTeamResponse $rajoutOverall = null,
        public array $markingByDistance = [],
        public array $rajoutByDistance = [],
        public ?PlayerTacticalInsightsCoverageResponse $coverage = null,
    ) {
    }
}
