<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class JumpShotStatsResponse
{
    /**
     * @param list<JumpShotStatsEvolutionPointResponse> $evolution
     * @param list<JumpShotStatsDistanceResponse> $byDistance
     * @param list<JumpShotStatsResultResponse> $byResult
     */
    public function __construct(
        public string $status,
        public JumpShotStatsSummaryResponse $summary,
        public array $evolution,
        public array $byDistance,
        public array $byResult,
    ) {
    }
}
