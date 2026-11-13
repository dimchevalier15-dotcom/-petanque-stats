<?php

declare(strict_types=1);

namespace App\Tests\Service\Tournament;

use App\Service\Tournament\RankingMatchInput;
use App\Service\Tournament\RankingTeamInput;
use App\Service\Tournament\TournamentRankingCalculator;
use PHPUnit\Framework\TestCase;

final class TournamentRankingCalculatorTest extends TestCase
{
    private TournamentRankingCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new TournamentRankingCalculator();
    }

    public function testWinDrawAndLossPointsAndGoalDifference(): void
    {
        $rows = $this->calculator->rank(
            [new RankingTeamInput(1, 'A'), new RankingTeamInput(2, 'B')],
            [new RankingMatchInput(1, 2, 13, 8)],
            1,
            0,
        );

        self::assertSame(1, $rows[0]->teamId);
        self::assertSame(1, $rows[0]->points);
        self::assertSame(1, $rows[0]->wins);
        self::assertSame(13, $rows[0]->pointsFor);
        self::assertSame(8, $rows[0]->pointsAgainst);
        self::assertSame(5, $rows[0]->pointsDifference);
        self::assertSame(0, $rows[1]->points);
        self::assertSame(1, $rows[1]->losses);
        self::assertSame(-5, $rows[1]->pointsDifference);
    }

    public function testDrawPointsAreConfigurableAndADefeatStaysAtZero(): void
    {
        $rows = $this->calculator->rank(
            [new RankingTeamInput(1, 'A'), new RankingTeamInput(2, 'B')],
            [new RankingMatchInput(1, 2, 10, 10)],
            3,
            1,
        );

        self::assertSame(1, $rows[0]->points);
        self::assertSame(1, $rows[0]->draws);
        self::assertSame(0, $rows[0]->wins);
        self::assertSame(1, $rows[1]->points);
    }

    public function testHeadToHeadSeparatesExactlyTwoTeams(): void
    {
        $rows = $this->calculator->rank(
            [
                new RankingTeamInput(1, 'A'),
                new RankingTeamInput(2, 'B'),
                new RankingTeamInput(3, 'C'),
            ],
            [
                new RankingMatchInput(1, 3, 13, 0),
                new RankingMatchInput(2, 1, 13, 8),
            ],
            1,
            0,
        );

        self::assertSame([2, 1, 3], array_map(static fn ($row) => $row->teamId, $rows));
        self::assertSame(1, $rows[0]->points);
        self::assertSame(1, $rows[1]->points);
        self::assertFalse($rows[0]->tieUnresolved);
        self::assertFalse($rows[1]->tieUnresolved);
        self::assertFalse($rows[2]->tieUnresolved);
    }

    public function testThreeTeamsTiedOnPointsUseGoalDifference(): void
    {
        $rows = $this->calculator->rank(
            [
                new RankingTeamInput(1, 'A'),
                new RankingTeamInput(2, 'B'),
                new RankingTeamInput(3, 'C'),
            ],
            [
                new RankingMatchInput(1, 2, 13, 5),
                new RankingMatchInput(2, 3, 13, 0),
                new RankingMatchInput(3, 1, 13, 12),
            ],
            1,
            0,
        );

        self::assertSame([1, 2, 3], array_map(static fn ($row) => $row->teamId, $rows));
        self::assertSame([7, 5, -12], array_map(static fn ($row) => $row->pointsDifference, $rows));
        self::assertFalse($rows[0]->tieUnresolved);
    }

    public function testADrawnHeadToHeadDoesNotTriggerAnUnresolvedTieWarning(): void
    {
        $rows = $this->calculator->rank(
            [new RankingTeamInput(1, 'A'), new RankingTeamInput(2, 'B')],
            [new RankingMatchInput(1, 2, 10, 10)],
            1,
            1,
        );

        self::assertFalse($rows[0]->tieUnresolved);
        self::assertFalse($rows[1]->tieUnresolved);
    }

    public function testTeamsWithoutMatchesAreNotMarkedAsUnresolvedTies(): void
    {
        $rows = $this->calculator->rank(
            [
                new RankingTeamInput(1, 'A'),
                new RankingTeamInput(2, 'B'),
                new RankingTeamInput(3, 'C'),
            ],
            [],
            1,
            0,
        );

        self::assertSame([1, 2, 3], array_map(static fn ($row) => $row->teamId, $rows));
        foreach ($rows as $row) {
            self::assertFalse($row->tieUnresolved);
        }
    }

    public function testAnUnresolvedTieKeepsADeterministicOrder(): void
    {
        $rows = $this->calculator->rank(
            [
                new RankingTeamInput(3, 'C'),
                new RankingTeamInput(1, 'A'),
                new RankingTeamInput(2, 'B'),
                new RankingTeamInput(4, 'D'),
            ],
            [
                new RankingMatchInput(1, 4, 13, 8),
                new RankingMatchInput(2, 4, 13, 8),
                new RankingMatchInput(3, 4, 13, 8),
            ],
            1,
            0,
        );

        self::assertSame([1, 2, 3, 4], array_map(static fn ($row) => $row->teamId, $rows));
        self::assertTrue($rows[0]->tieUnresolved);
        self::assertTrue($rows[1]->tieUnresolved);
        self::assertTrue($rows[2]->tieUnresolved);
        self::assertFalse($rows[3]->tieUnresolved);
    }
}
