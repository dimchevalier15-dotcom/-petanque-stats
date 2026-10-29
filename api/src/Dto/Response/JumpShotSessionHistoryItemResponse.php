<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class JumpShotSessionHistoryItemResponse
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public string $finishedAt,
        public int $seriesCount,
        public int $totalThrows,
    ) {
    }
}
