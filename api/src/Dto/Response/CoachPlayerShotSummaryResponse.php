<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class CoachPlayerShotSummaryResponse
{
    public function __construct(
        public ?float $average,
        public ?int $successCount,
        public ?int $totalCount,
    ) {
    }
}
