<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class RingsThrowSummaryResponse
{
    public function __construct(
        public int $position,
        public string $target,
        public bool $success,
        public bool $carreau,
        public int $score,
    ) {
    }
}
