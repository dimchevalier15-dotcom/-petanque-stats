<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PointingShotSummary
{
    public function __construct(
        public int $distance,
        public string $result,
        public int $score,
    ) {
    }
}
