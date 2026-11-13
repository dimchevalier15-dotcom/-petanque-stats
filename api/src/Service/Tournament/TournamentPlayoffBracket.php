<?php

declare(strict_types=1);

namespace App\Service\Tournament;

use App\Enum\TournamentPlayoffRound;

final class TournamentPlayoffBracket
{
    /**
     * @param list<int> $qualifiedTeamIds index 0 is rank 1
     * @return list<PlannedPlayoffMatch>
     */
    public function plan(array $qualifiedTeamIds): array
    {
        if (count($qualifiedTeamIds) < 8) {
            throw new TournamentRuleException('not_enough_teams', 'Eight teams are required for the play-offs.', 409);
        }

        $qualified = array_slice(array_values($qualifiedTeamIds), 0, 8);

        return [
            new PlannedPlayoffMatch(TournamentPlayoffRound::QUARTER, 1, $qualified[0], $qualified[7], null, null),
            new PlannedPlayoffMatch(TournamentPlayoffRound::QUARTER, 2, $qualified[3], $qualified[4], null, null),
            new PlannedPlayoffMatch(TournamentPlayoffRound::QUARTER, 3, $qualified[1], $qualified[6], null, null),
            new PlannedPlayoffMatch(TournamentPlayoffRound::QUARTER, 4, $qualified[2], $qualified[5], null, null),
            new PlannedPlayoffMatch(TournamentPlayoffRound::SEMI, 1, null, null, 1, 2),
            new PlannedPlayoffMatch(TournamentPlayoffRound::SEMI, 2, null, null, 3, 4),
            new PlannedPlayoffMatch(TournamentPlayoffRound::FINAL, 1, null, null, 1, 2),
        ];
    }
}
