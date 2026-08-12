<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TrainingStatsResponse
{
    /**
     * @param list<TrainingStatsEvolutionPointResponse> $evolution
     * @param list<TrainingStatsTypeResponse> $byType
     * @param list<TrainingStatsDistanceResponse> $byDistance
     */
    public function __construct(
        public string $status,
        public TrainingStatsSummaryResponse $summary,
        public array $evolution,
        public array $byType,
        public array $byDistance,
    ) {
    }
}
