<?php

declare(strict_types=1);

namespace App\Service\Tournament;

final class PlannedTournamentMatch
{
    public function __construct(
        public ?int $realTeam1Id,
        public ?int $realTeam2Id,
        public ?int $fakeTeam1Index,
        public ?int $fakeTeam2Index,
    ) {
    }

    public function isFictional(): bool
    {
        return $this->fakeTeam1Index !== null || $this->fakeTeam2Index !== null;
    }
}
