<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightsPointDominanceResponse
{
    public function __construct(
        public int $endsWonWhenOpened,
        public int $endsOpened,
    ) {
    }
}
