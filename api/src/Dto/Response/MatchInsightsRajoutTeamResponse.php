<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightsRajoutTeamResponse
{
    public function __construct(
        public MatchInsightsMarkingRateResponse $point,
        public MatchInsightsMarkingRateResponse $tir,
    ) {
    }
}
