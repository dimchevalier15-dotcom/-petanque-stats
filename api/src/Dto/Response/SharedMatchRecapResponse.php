<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class SharedMatchRecapResponse
{
    public function __construct(
        public MatchSummaryResponse $summary,
        public MatchContextResponse $context,
        public ?string $competitionLabel = null,
        public ?MatchInsightsResponse $insights = null,
    ) {
    }
}
