<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerStatsByNatureResponse
{
    public function __construct(
        public string $nature,
        public int $matchCount,
        public int $ballCount,
        public float $average,
    ) {
    }
}
