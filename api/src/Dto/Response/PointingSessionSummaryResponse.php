<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PointingSessionSummaryResponse
{
    /** @param list<PointingWorkshopSummary> $workshops */
    public function __construct(
        public int $id,
        public string $createdAt,
        public string $playedAt,
        public ?string $finishedAt,
        public ?int $totalScore,
        public ?string $contextNature,
        public ?string $title,
        public ?string $description,
        public array $workshops,
    ) {
    }
}
