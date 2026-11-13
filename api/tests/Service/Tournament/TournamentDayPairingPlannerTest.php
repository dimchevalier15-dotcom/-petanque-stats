<?php

declare(strict_types=1);

namespace App\Tests\Service\Tournament;

use App\Service\Tournament\PairingImpossibleException;
use App\Service\Tournament\TournamentDayPairingPlanner;
use App\Service\Tournament\TournamentPairKey;
use PHPUnit\Framework\TestCase;

final class TournamentDayPairingPlannerTest extends TestCase
{
    private TournamentDayPairingPlanner $planner;

    protected function setUp(): void
    {
        $this->planner = new TournamentDayPairingPlanner();
    }

    public function testARealTeamPlaysOnceAndBlockedPairsAreNeverReused(): void
    {
        $blocked = [];
        $seen = [];
        for ($day = 0; $day < 3; ++$day) {
            $matches = $this->planner->plan([1, 2, 3, 4], [], $blocked);
            self::assertCount(2, $matches);
            $usedTeams = [];
            foreach ($matches as $match) {
                self::assertFalse($match->isFictional());
                self::assertNotNull($match->realTeam1Id);
                self::assertNotNull($match->realTeam2Id);
                $key = TournamentPairKey::of($match->realTeam1Id, $match->realTeam2Id);
                self::assertArrayNotHasKey($key, $seen);
                $seen[$key] = true;
                $blocked[$key] = true;
                self::assertArrayNotHasKey($match->realTeam1Id, $usedTeams);
                self::assertArrayNotHasKey($match->realTeam2Id, $usedTeams);
                $usedTeams[$match->realTeam1Id] = true;
                $usedTeams[$match->realTeam2Id] = true;
            }
        }

        self::assertCount(6, $seen);
        $this->expectException(PairingImpossibleException::class);
        $this->planner->plan([1, 2, 3, 4], [], $blocked);
    }

    public function testOddTeamCountLeavesOneTeamWithoutAMatch(): void
    {
        $matches = $this->planner->plan([1, 2, 3], [], []);

        self::assertCount(1, $matches);
        $ids = [$matches[0]->realTeam1Id, $matches[0]->realTeam2Id];
        sort($ids);
        self::assertContains($ids, [[1, 2], [1, 3], [2, 3]]);
    }

    public function testOnlyTheRemainingLegalMatchingIsUsed(): void
    {
        $blocked = [
            TournamentPairKey::of(1, 3) => true,
            TournamentPairKey::of(1, 4) => true,
            TournamentPairKey::of(2, 3) => true,
            TournamentPairKey::of(2, 4) => true,
        ];
        $matches = $this->planner->plan([1, 2, 3, 4], [], $blocked);
        $pairs = [];
        foreach ($matches as $match) {
            self::assertNotNull($match->realTeam1Id);
            self::assertNotNull($match->realTeam2Id);
            $pairs[] = TournamentPairKey::of($match->realTeam1Id, $match->realTeam2Id);
        }
        sort($pairs);

        self::assertSame(['1-2', '3-4'], $pairs);
    }

    public function testRealConfrontationsArePreferredOverFictionalTeams(): void
    {
        $matches = $this->planner->plan([1, 2], [0], []);

        self::assertCount(1, $matches);
        self::assertFalse($matches[0]->isFictional());
    }

    public function testEightTeamsCanBeDecomposedWithoutRepeatingAPair(): void
    {
        $blocked = [];
        $teamIds = range(1, 8);
        for ($day = 0; $day < 7; ++$day) {
            $matches = $this->planner->plan($teamIds, [], $blocked);
            self::assertCount(4, $matches);
            foreach ($matches as $match) {
                self::assertNotNull($match->realTeam1Id);
                self::assertNotNull($match->realTeam2Id);
                $key = TournamentPairKey::of($match->realTeam1Id, $match->realTeam2Id);
                self::assertArrayNotHasKey($key, $blocked);
                $blocked[$key] = true;
            }
        }

        self::assertCount(28, $blocked);
    }

    public function testAFakeTeamCanPlayWhenTheRealPairIsAlreadyUsed(): void
    {
        $matches = $this->planner->plan([1, 2], [0], [TournamentPairKey::of(1, 2) => true]);

        self::assertCount(1, $matches);
        self::assertTrue($matches[0]->isFictional());
        $realIds = array_values(array_filter([$matches[0]->realTeam1Id, $matches[0]->realTeam2Id]));
        self::assertCount(1, $realIds);
    }
}
