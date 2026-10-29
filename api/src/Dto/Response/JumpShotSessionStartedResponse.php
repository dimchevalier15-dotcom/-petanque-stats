<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class JumpShotSessionStartedResponse
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public int $currentDistance,
        public int $seriesCount,
    ) {
    }
}
