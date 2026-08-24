<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchSummaryEndTotal
{
    public function __construct(
        public int $endIndex,
        public int $total,
    ) {
    }
}
