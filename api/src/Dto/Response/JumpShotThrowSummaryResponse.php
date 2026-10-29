<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class JumpShotThrowSummaryResponse
{
    public function __construct(
        public int $position,
        public string $result,
    ) {
    }
}
