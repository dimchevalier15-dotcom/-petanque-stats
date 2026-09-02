<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerTacticalInsightsCoverageResponse
{
    public function __construct(
        public int $matchesEligible,
        public int $matchesAnalyzed,
        public int $endsAnalyzed,
        public float $distanceSampleRate,
    ) {
    }
}
