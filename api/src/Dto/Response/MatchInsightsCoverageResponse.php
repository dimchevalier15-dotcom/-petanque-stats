<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightsCoverageResponse
{
    public function __construct(
        public float $distanceSampleRate,
        public int $endsAnalyzed,
    ) {
    }
}
