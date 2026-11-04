<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class RingsStatsResponse
{
    /**
     * @param list<RingsStatsEvolutionPointResponse> $evolution
     * @param list<RingsStatsTargetResponse> $byTarget
     * @param list<RingsStatsScoreDecileResponse> $scoreByDecile
     */
    public function __construct(
        public string $status,
        public RingsStatsSummaryResponse $summary,
        public array $evolution,
        public array $byTarget,
        public array $scoreByDecile,
    ) {
    }
}
