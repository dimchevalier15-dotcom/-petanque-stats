<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerStatsByDistanceResponse
{
    public function __construct(
        public string $bucket,
        public int $ballCount,
        public float $average,
    ) {
    }
}
