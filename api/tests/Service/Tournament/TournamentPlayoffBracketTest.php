<?php

declare(strict_types=1);

namespace App\Tests\Service\Tournament;

use App\Enum\TournamentPlayoffRound;
use App\Service\Tournament\TournamentPlayoffBracket;
use PHPUnit\Framework\TestCase;

final class TournamentPlayoffBracketTest extends TestCase
{
    public function testQuarterFinalsFollowTheQualificationBracket(): void
    {
        $plans = (new TournamentPlayoffBracket())->plan([1, 2, 3, 4, 5, 6, 7, 8]);
        $quarters = array_values(array_filter(
            $plans,
            static fn ($plan) => $plan->round === TournamentPlayoffRound::QUARTER,
        ));

        self::assertSame([1, 8], [$quarters[0]->team1Id, $quarters[0]->team2Id]);
        self::assertSame([4, 5], [$quarters[1]->team1Id, $quarters[1]->team2Id]);
        self::assertSame([2, 7], [$quarters[2]->team1Id, $quarters[2]->team2Id]);
        self::assertSame([3, 6], [$quarters[3]->team1Id, $quarters[3]->team2Id]);
        self::assertSame(TournamentPlayoffRound::SEMI, $plans[4]->round);
        self::assertSame([1, 2], [$plans[4]->homeSourceSlot, $plans[4]->awaySourceSlot]);
        self::assertSame(TournamentPlayoffRound::FINAL, $plans[6]->round);
    }

    public function testSixTeamsGetByesForTheTopTwoAndThreeSixQuarterFinals(): void
    {
        $plans = (new TournamentPlayoffBracket())->plan([11, 22, 33, 44, 55, 66]);
        $quarters = array_values(array_filter(
            $plans,
            static fn ($plan) => $plan->round === TournamentPlayoffRound::QUARTER,
        ));

        self::assertSame(11, $quarters[0]->byeTeamId);
        self::assertSame([44, 55], [$quarters[1]->team1Id, $quarters[1]->team2Id]);
        self::assertSame(22, $quarters[2]->byeTeamId);
        self::assertSame([33, 66], [$quarters[3]->team1Id, $quarters[3]->team2Id]);
    }
}
