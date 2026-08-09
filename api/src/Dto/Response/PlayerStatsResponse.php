<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerStatsResponse
{
    /**
     * @param list<PlayerStatsEvolutionPointResponse> $evolution
     * @param list<PlayerStatsByNatureResponse> $byNature
     */
    public function __construct(
        public string $status,
        public ?int $playerId,
        public ?string $displayName,
        public PlayerStatsSummaryResponse $summary,
        public ?MatchSummaryShotBreakdown $overall,
        public ?MatchSummaryShotBreakdown $point,
        public ?MatchSummaryShotBreakdown $tir,
        public array $evolution,
        public array $byNature,
    ) {
    }
}
