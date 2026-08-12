<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TrainingStatsTypeResponse
{
    public function __construct(
        public string $type,
        public int $ballCount,
        public float $successRate,
        public float $averageScore,
    ) {
    }
}
