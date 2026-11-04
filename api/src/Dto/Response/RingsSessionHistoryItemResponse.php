<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class RingsSessionHistoryItemResponse
{
    public function __construct(
        public int $id,
        public string $mode,
        public string $playedAt,
        public string $finishedAt,
        public int $totalScore,
    ) {
    }
}
