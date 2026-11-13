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
        $count = count($qualifiedTeamIds);
        if ($count < 2) {
            throw new TournamentRuleException('not_enough_teams', 'At least two teams are required for the play-offs.', 409);
        }

        $byRank = array_values($qualifiedTeamIds);
        $teamAt = static function (int $rank) use ($byRank, $count): ?int {
            if ($rank < 1 || $rank > $count) {
                return null;
            }

            return $byRank[$rank - 1];
        };

        $plans = [];
        $quarterPairings = [[1, 8], [4, 5], [2, 7], [3, 6]];
        foreach ($quarterPairings as $index => [$seedHome, $seedAway]) {
            $slot = $index + 1;
            $team1 = $teamAt($seedHome);
            $team2 = $teamAt($seedAway);
            if ($team1 !== null && $team2 !== null) {
                $plans[] = new PlannedPlayoffMatch(TournamentPlayoffRound::QUARTER, $slot, $team1, $team2, null, null);
                continue;
            }
            if ($team1 !== null) {
                $plans[] = new PlannedPlayoffMatch(TournamentPlayoffRound::QUARTER, $slot, null, null, null, null, $team1);
                continue;
            }
            if ($team2 !== null) {
                $plans[] = new PlannedPlayoffMatch(TournamentPlayoffRound::QUARTER, $slot, null, null, null, null, $team2);
            }
        }

        $plans[] = new PlannedPlayoffMatch(TournamentPlayoffRound::SEMI, 1, null, null, 1, 2);
        $plans[] = new PlannedPlayoffMatch(TournamentPlayoffRound::SEMI, 2, null, null, 3, 4);
        $plans[] = new PlannedPlayoffMatch(TournamentPlayoffRound::FINAL, 1, null, null, 1, 2);

        return $plans;
    }
}
