<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class CoachPlayerPointingSummaryResponse
{
    public function __construct(
        public int $sessionsCount,
        public ?float $averageSessionScore,
        public ?int $bestSessionScore,
    ) {
    }
}
