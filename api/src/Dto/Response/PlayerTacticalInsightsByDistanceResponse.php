<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerTacticalInsightsByDistanceResponse
{
    public function __construct(
        public string $bucket,
        public MatchInsightsMarkingRateResponse $point,
        public MatchInsightsMarkingRateResponse $tir,
    ) {
    }
}
