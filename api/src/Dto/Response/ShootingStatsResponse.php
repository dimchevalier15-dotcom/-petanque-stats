<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ShootingStatsResponse
{
    /**
     * @param list<ShootingStatsEvolutionPointResponse> $evolution
     * @param list<ShootingStatsWorkshopResponse> $byWorkshop
     * @param list<ShootingStatsDistanceResponse> $byDistance
     * @param list<ShootingStatsResultResponse> $byResult
     * @param list<ShootingStatsCellResponse> $heatmap
     */
    public function __construct(
        public string $status,
        public ShootingStatsSummaryResponse $summary,
        public array $evolution,
        public array $byWorkshop,
        public array $byDistance,
        public array $byResult,
        public array $heatmap,
    ) {
    }
}
