<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Enum\GameType;
use App\Enum\MatchNature;

final readonly class MatchHistoryFilters
{
    public function __construct(
        public ?MatchNature $nature = null,
        public ?GameType $type = null,
        public ?DateRange $range = null,
        public ?int $competitionId = null,
        public bool $includeRefused = false,
    ) {
    }
}
