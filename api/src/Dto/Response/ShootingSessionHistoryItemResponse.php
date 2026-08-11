<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ShootingSessionHistoryItemResponse
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public string $finishedAt,
        public int $totalScore,
        public ?string $title,
    ) {
    }
}
