<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ShootingShotSummary
{
    public function __construct(
        public int $distance,
        public string $result,
        public int $score,
    ) {
    }
}
