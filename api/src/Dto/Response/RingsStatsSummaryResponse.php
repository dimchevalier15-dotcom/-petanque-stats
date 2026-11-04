<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class RingsStatsSummaryResponse
{
    public function __construct(
        public int $sessionsCount,
        public int $totalThrows,
        public ?float $averageSessionScore,
        public ?int $bestSessionScore,
        public ?float $overallSuccessRate,
        public int $maxSessionScore,
        public int $carreauCount,
        public ?float $carreauRate,
    ) {
    }
}
