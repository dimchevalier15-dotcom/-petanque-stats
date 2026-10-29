<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PointingSessionHistoryItemResponse
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public string $playedAt,
        public string $finishedAt,
        public int $totalScore,
        public ?string $contextNature,
        public ?string $title,
    ) {
    }
}
