<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ShootingStatsEvolutionPointResponse
{
    public function __construct(
        public int $sessionId,
        public string $date,
        public int $totalScore,
    ) {
    }
}
