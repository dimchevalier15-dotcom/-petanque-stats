<?php

declare(strict_types=1);

namespace App\Service\Tournament;

use App\Enum\TournamentPlayoffRound;

final class PlannedPlayoffMatch
{
    public function __construct(
        public TournamentPlayoffRound $round,
        public int $slot,
        public ?int $team1Id,
        public ?int $team2Id,
        public ?int $homeSourceSlot,
        public ?int $awaySourceSlot,
        public ?int $byeTeamId = null,
    ) {
    }
}
