<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class RingsSessionSummaryResponse
{
    /** @param list<RingsThrowSummaryResponse> $throws */
    public function __construct(
        public int $id,
        public string $mode,
        public string $createdAt,
        public string $playedAt,
        public ?string $finishedAt,
        public ?int $totalScore,
        public array $throws,
    ) {
    }
}
