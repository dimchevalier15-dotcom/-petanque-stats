<?php

declare(strict_types=1);

namespace App\Service\Tournament;

final class TournamentRankingCalculator
{
    /**
     * @param list<RankingTeamInput> $teams
     * @param list<RankingMatchInput> $matches championship matches between real teams only
     * @return list<TournamentStanding>
     */
    public function rank(array $teams, array $matches, int $pointsForWin, int $pointsForDraw): array
    {
        /** @var array<int, array{name: string, played: int, wins: int, draws: int, losses: int, points: int, pointsFor: int, pointsAgainst: int}> $rows */
        $rows = [];
        foreach ($teams as $team) {
            $rows[$team->id] = [
                'name' => $team->name,
                'played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'points' => 0,
                'pointsFor' => 0,
                'pointsAgainst' => 0,
            ];
        }

        /** @var array<string, int> $headToHeadWinner 0 when drawn */
        $headToHeadWinner = [];
        foreach ($matches as $match) {
            if (!isset($rows[$match->team1Id], $rows[$match->team2Id])) {
                continue;
            }
            $this->applyMatch($rows[$match->team1Id], $rows[$match->team2Id], $match, $pointsForWin, $pointsForDraw);
            $winnerId = 0;
            if ($match->score1 > $match->score2) {
                $winnerId = $match->team1Id;
            } elseif ($match->score2 > $match->score1) {
                $winnerId = $match->team2Id;
            }
            $headToHeadWinner[TournamentPairKey::of($match->team1Id, $match->team2Id)] = $winnerId;
        }

        /** @var array<int, list<int>> $groups */
        $groups = [];
        foreach ($rows as $teamId => $row) {
            $groups[$row['points']][] = $teamId;
        }
        krsort($groups);

        $ordered = [];
        foreach ($groups as $teamIds) {
            foreach ($this->orderTieGroup($teamIds, $rows, $headToHeadWinner) as $teamId) {
                $ordered[] = $teamId;
            }
        }

        $standings = [];
        $position = 1;
        foreach ($ordered as $teamId) {
            $row = $rows[$teamId];
            $standings[] = new TournamentStanding(
                $position,
                $teamId,
                $row['name'],
                $row['played'],
                $row['wins'],
                $row['draws'],
                $row['losses'],
                $row['points'],
                $row['pointsFor'],
                $row['pointsAgainst'],
                $row['pointsFor'] - $row['pointsAgainst'],
                $row['tieUnresolved'] ?? false,
            );
            ++$position;
        }

        return $standings;
    }

    /**
     * @param array{name: string, played: int, wins: int, draws: int, losses: int, points: int, pointsFor: int, pointsAgainst: int} $home
     * @param array{name: string, played: int, wins: int, draws: int, losses: int, points: int, pointsFor: int, pointsAgainst: int} $away
     */
    private function applyMatch(array &$home, array &$away, RankingMatchInput $match, int $pointsForWin, int $pointsForDraw): void
    {
        ++$home['played'];
        ++$away['played'];
        $home['pointsFor'] += $match->score1;
        $home['pointsAgainst'] += $match->score2;
        $away['pointsFor'] += $match->score2;
        $away['pointsAgainst'] += $match->score1;
        if ($match->score1 === $match->score2) {
            ++$home['draws'];
            ++$away['draws'];
            $home['points'] += $pointsForDraw;
            $away['points'] += $pointsForDraw;

            return;
        }
        if ($match->score1 > $match->score2) {
            ++$home['wins'];
            ++$away['losses'];
            $home['points'] += $pointsForWin;

            return;
        }
        ++$away['wins'];
        ++$home['losses'];
        $away['points'] += $pointsForWin;
    }

    /**
     * @param list<int> $teamIds
     * @param array<int, array{name: string, played: int, wins: int, draws: int, losses: int, points: int, pointsFor: int, pointsAgainst: int, tieUnresolved?: bool}> $rows
     * @param array<string, int> $headToHeadWinner
     * @return list<int>
     */
    private function orderTieGroup(array $teamIds, array &$rows, array $headToHeadWinner): array
    {
        if (count($teamIds) === 1) {
            return $teamIds;
        }
        if (count($teamIds) === 2) {
            return $this->orderPair($teamIds[0], $teamIds[1], $rows, $headToHeadWinner);
        }

        $hasPlayed = false;
        foreach ($teamIds as $teamId) {
            if ($rows[$teamId]['played'] > 0) {
                $hasPlayed = true;
                break;
            }
        }
        if (!$hasPlayed) {
            sort($teamIds);

            return $teamIds;
        }

        usort($teamIds, function (int $leftId, int $rightId) use ($rows): int {
            $leftDiff = $rows[$leftId]['pointsFor'] - $rows[$leftId]['pointsAgainst'];
            $rightDiff = $rows[$rightId]['pointsFor'] - $rows[$rightId]['pointsAgainst'];
            if ($leftDiff !== $rightDiff) {
                return $rightDiff <=> $leftDiff;
            }

            return $leftId <=> $rightId;
        });

        $differenceCounts = [];
        foreach ($teamIds as $teamId) {
            $difference = $rows[$teamId]['pointsFor'] - $rows[$teamId]['pointsAgainst'];
            $differenceCounts[$difference] = ($differenceCounts[$difference] ?? 0) + 1;
        }
        foreach ($teamIds as $teamId) {
            $difference = $rows[$teamId]['pointsFor'] - $rows[$teamId]['pointsAgainst'];
            if ($differenceCounts[$difference] > 1) {
                $rows[$teamId]['tieUnresolved'] = true;
            }
        }

        return $teamIds;
    }

    /**
     * @param array<int, array{name: string, played: int, wins: int, draws: int, losses: int, points: int, pointsFor: int, pointsAgainst: int, tieUnresolved?: bool}> $rows
     * @param array<string, int> $headToHeadWinner
     * @return list<int>
     */
    private function orderPair(int $leftId, int $rightId, array &$rows, array $headToHeadWinner): array
    {
        $pairKey = TournamentPairKey::of($leftId, $rightId);
        if (!\array_key_exists($pairKey, $headToHeadWinner)) {
            return $this->orderByDiffThenId($leftId, $rightId, $rows);
        }
        $winnerId = $headToHeadWinner[$pairKey];
        if ($winnerId === $leftId) {
            return [$leftId, $rightId];
        }
        if ($winnerId === $rightId) {
            return [$rightId, $leftId];
        }

        return $this->orderByDiffThenId($leftId, $rightId, $rows);
    }

    /**
     * @param array<int, array{name: string, played: int, wins: int, draws: int, losses: int, points: int, pointsFor: int, pointsAgainst: int}> $rows
     * @return list<int>
     */
    private function orderByDiffThenId(int $leftId, int $rightId, array $rows): array
    {
        $leftDiff = $rows[$leftId]['pointsFor'] - $rows[$leftId]['pointsAgainst'];
        $rightDiff = $rows[$rightId]['pointsFor'] - $rows[$rightId]['pointsAgainst'];
        if ($leftDiff !== $rightDiff) {
            return $leftDiff > $rightDiff ? [$leftId, $rightId] : [$rightId, $leftId];
        }

        return $leftId < $rightId ? [$leftId, $rightId] : [$rightId, $leftId];
    }
}
