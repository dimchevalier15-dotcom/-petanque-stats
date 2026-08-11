<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ShootingWorkshopSummary
{
    /** @param list<ShootingShotSummary> $shots */
    public function __construct(
        public int $workshop,
        public int $totalScore,
        public array $shots,
    ) {
    }
}
