<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightsMarkingRateResponse
{
    public function __construct(
        public int $made,
        public int $attempts,
        public ?float $rate,
    ) {
    }
}
