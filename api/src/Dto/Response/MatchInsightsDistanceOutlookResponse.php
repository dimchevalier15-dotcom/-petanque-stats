<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightsDistanceOutlookResponse
{
    /**
     * @param list<MatchInsightsByDistanceResponse> $competitiveBuckets
     */
    public function __construct(
        public ?string $singleDominantTeam,
        public array $competitiveBuckets,
    ) {
    }
}
