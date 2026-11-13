<?php

declare(strict_types=1);

namespace App\Service\Tournament;

final class TournamentDayPairingPlanner
{
    /**
     * Real teams are paired first on remaining championship confrontations.
     * Fake teams only fill the teams left without a real opponent.
     *
     * @param list<int> $realTeamIds
     * @param list<int> $fakeTeamIndexes
     * @param array<string, true> $blockedPairKeys
     * @return list<PlannedTournamentMatch>
     */
    public function plan(array $realTeamIds, array $fakeTeamIndexes, array $blockedPairKeys): array
    {
        $realTeamIds = array_values(array_unique($realTeamIds));
        $fakeTeamIndexes = array_values($fakeTeamIndexes);
        if (count($realTeamIds) + count($fakeTeamIndexes) < 2) {
            throw new PairingImpossibleException('Not enough teams.');
        }

        shuffle($realTeamIds);
        $realMatching = $this->maximumRealMatching($realTeamIds, $blockedPairKeys);
        $planned = [];
        $used = [];
        foreach ($realMatching as [$teamAId, $teamBId]) {
            $used[$teamAId] = true;
            $used[$teamBId] = true;
            $planned[] = $this->withRandomSideOrder(
                new PlannedTournamentMatch($teamAId, $teamBId, null, null),
            );
        }

        $leftoverReals = [];
        foreach ($realTeamIds as $teamId) {
            if (!isset($used[$teamId])) {
                $leftoverReals[] = $teamId;
            }
        }
        $remainingFakes = $fakeTeamIndexes;
        shuffle($leftoverReals);
        shuffle($remainingFakes);
        while ($leftoverReals !== [] && $remainingFakes !== []) {
            $planned[] = $this->withRandomSideOrder(new PlannedTournamentMatch(
                array_shift($leftoverReals),
                null,
                null,
                array_shift($remainingFakes),
            ));
        }
        while (count($remainingFakes) >= 2) {
            $planned[] = $this->withRandomSideOrder(new PlannedTournamentMatch(
                null,
                null,
                array_shift($remainingFakes),
                array_shift($remainingFakes),
            ));
        }

        if ($planned === []) {
            throw new PairingImpossibleException('No legal match remains.');
        }

        return $planned;
    }

    /**
     * @param list<int> $teamIds
     * @param array<string, true> $blockedPairKeys
     * @return list<array{0: int, 1: int}>
     */
    private function maximumRealMatching(array $teamIds, array $blockedPairKeys): array
    {
        $best = [];
        $this->search($teamIds, $blockedPairKeys, [], [], $best, intdiv(count($teamIds), 2));

        return $best;
    }

    /**
     * @param list<int> $teamIds
     * @param array<string, true> $blockedPairKeys
     * @param array<int, true> $used
     * @param list<array{0: int, 1: int}> $current
     * @param list<array{0: int, 1: int}> $best
     */
    private function search(
        array $teamIds,
        array $blockedPairKeys,
        array $used,
        array $current,
        array &$best,
        int $target,
    ): void {
        if (count($best) === $target) {
            return;
        }
        $remaining = 0;
        foreach ($teamIds as $teamId) {
            if (!isset($used[$teamId])) {
                ++$remaining;
            }
        }
        if (count($current) + intdiv($remaining, 2) <= count($best)) {
            return;
        }

        $teamId = null;
        foreach ($teamIds as $candidateId) {
            if (!isset($used[$candidateId])) {
                $teamId = $candidateId;
                break;
            }
        }
        if ($teamId === null) {
            if (count($current) > count($best)) {
                $best = $current;
            }

            return;
        }

        $partners = [];
        foreach ($teamIds as $otherId) {
            if ($otherId === $teamId || isset($used[$otherId])) {
                continue;
            }
            if (!isset($blockedPairKeys[TournamentPairKey::of($teamId, $otherId)])) {
                $partners[] = $otherId;
            }
        }
        shuffle($partners);
        foreach ($partners as $otherId) {
            $used[$teamId] = true;
            $used[$otherId] = true;
            $current[] = [$teamId, $otherId];
            $this->search($teamIds, $blockedPairKeys, $used, $current, $best, $target);
            array_pop($current);
            unset($used[$teamId], $used[$otherId]);
            if (count($best) === $target) {
                return;
            }
        }

        $used[$teamId] = true;
        $this->search($teamIds, $blockedPairKeys, $used, $current, $best, $target);
        unset($used[$teamId]);
    }

    private function withRandomSideOrder(PlannedTournamentMatch $match): PlannedTournamentMatch
    {
        if (random_int(0, 1) === 0) {
            return $match;
        }

        return new PlannedTournamentMatch(
            $match->realTeam2Id,
            $match->realTeam1Id,
            $match->fakeTeam2Index,
            $match->fakeTeam1Index,
        );
    }
}
