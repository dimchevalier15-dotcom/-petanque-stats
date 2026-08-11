<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ShootingStatsSummaryResponse
{
    public function __construct(
        public int $sessionsCount,
        public int $totalShots,
        public ?float $averageSessionScore,
        public ?int $bestSessionScore,
    ) {
    }
}
