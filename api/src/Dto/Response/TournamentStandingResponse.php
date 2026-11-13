<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TournamentStandingResponse
{
    public function __construct(
        public int $position,
        public int $teamId,
        public string $teamName,
        public int $played,
        public int $wins,
        public int $draws,
        public int $losses,
        public int $points,
        public int $pointsFor,
        public int $pointsAgainst,
        public int $pointsDifference,
        public bool $tieUnresolved,
        public bool $qualifiedForPlayoffs,
    ) {
    }
}
