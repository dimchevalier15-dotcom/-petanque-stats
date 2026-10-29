<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class JumpShotStatsEvolutionPointResponse
{
    public function __construct(
        public int $sessionId,
        public string $date,
        public int $seriesCount,
    ) {
    }
}
