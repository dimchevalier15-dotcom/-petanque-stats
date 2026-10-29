<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class JumpShotSeriesSummaryResponse
{
    /**
     * @param list<JumpShotThrowSummaryResponse> $throws
     */
    public function __construct(
        public int $seriesNumber,
        public int $distance,
        public int $successCount,
        public bool $isSpecialSeries,
        public string $consequence,
        public string $createdAt,
        public array $throws,
    ) {
    }
}
