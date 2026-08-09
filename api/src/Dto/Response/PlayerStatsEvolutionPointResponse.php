<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerStatsEvolutionPointResponse
{
    public function __construct(
        public int $matchId,
        public string $date,
        public float $average,
        public bool $victory,
    ) {
    }
}
