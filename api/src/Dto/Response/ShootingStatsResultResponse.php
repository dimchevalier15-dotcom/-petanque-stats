<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ShootingStatsResultResponse
{
    public function __construct(
        public string $result,
        public int $count,
    ) {
    }
}
