<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class JumpShotSessionSummaryResponse
{
    /**
     * @param list<JumpShotSeriesSummaryResponse> $series
     */
    public function __construct(
        public int $id,
        public string $createdAt,
        public ?string $finishedAt,
        public bool $won,
        public int $currentDistance,
        public int $seriesCount,
        public string $progressionMode,
        public array $series,
    ) {
    }
}
