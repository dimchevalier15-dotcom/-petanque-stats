<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightsHeldEndErrorResponse
{
    public function __construct(
        public int $minusTwoCount,
        public int $ballsPlayed,
        public ?float $rate,
    ) {
    }
}
