<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PointingStatsResponse
{
    /**
     * @param list<PointingStatsEvolutionPointResponse> $evolution
     * @param list<PointingStatsWorkshopResponse> $byWorkshop
     * @param list<PointingStatsDistanceResponse> $byDistance
     * @param list<PointingStatsResultResponse> $byResult
     * @param list<PointingStatsCellResponse> $heatmap
     */
    public function __construct(
        public string $status,
        public PointingStatsSummaryResponse $summary,
        public array $evolution,
        public array $byWorkshop,
        public array $byDistance,
        public array $byResult,
        public array $heatmap,
    ) {
    }
}
