<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class JumpShotStatsSummaryResponse
{
    public function __construct(
        public int $sessionsCount,
        public int $totalSeries,
        public int $totalThrows,
        public ?float $averageSeriesPerSession,
        public ?int $fewestSeriesToWin,
        public ?float $overallSuccessRate,
        public int $specialSeriesCount,
    ) {
    }
}
