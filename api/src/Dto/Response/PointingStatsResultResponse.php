<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PointingStatsResultResponse
{
    public function __construct(
        public string $result,
        public int $count,
    ) {
    }
}
