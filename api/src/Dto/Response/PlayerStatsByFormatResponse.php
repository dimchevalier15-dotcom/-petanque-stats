<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerStatsByFormatResponse
{
    public function __construct(
        public string $type,
        public int $matchCount,
        public int $victories,
        public int $ballCount,
        public float $average,
    ) {
    }
}
