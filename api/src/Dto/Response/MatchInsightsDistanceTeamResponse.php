<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightsDistanceTeamResponse
{
    public function __construct(
        public float $average,
        public int $balls,
        public ?float $pointSuccessRate,
    ) {
    }
}
