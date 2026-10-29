<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PointingWorkshopSummary
{
    /** @param list<PointingShotSummary> $shots */
    public function __construct(
        public int $workshop,
        public int $totalScore,
        public array $shots,
    ) {
    }
}
