<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Request\CompleteMatchEndBallDto;
use App\Dto\Request\CompleteMatchEndDto;
use App\Dto\Request\CompleteMatchRequest;
use App\Dto\Request\CreateMatchRequest;
use App\Entity\Player;
use App\Entity\User;
use App\Service\MatchRecordingService;
use App\Service\MatchService;
use App\Service\MatchSummaryService;
use App\Tests\Support\MatchTestHelpers;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use App\Tests\Support\KernelDatabaseTestCase;

final class MatchSummaryServiceTest extends KernelDatabaseTestCase
{
    use MatchTestHelpers;

    private MatchSummaryService $summary;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->matchService = $container->get(MatchService::class);
        $this->recording = $container->get(MatchRecordingService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
        $this->summary = $container->get(MatchSummaryService::class);
    }

    public function testSummaryReturnsScoresAndTrackedPlayerAverages(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseCompleteRequest($playerAId, $playerBId);
        $ballA = new CompleteMatchEndBallDto();
        $ballA->playerId = $playerAId;
        $ballA->notes = [2, 0];
        $ballA->shotTypes = ['point', 'tir'];
        $req->ends[0]->balls = [$ballA];
        $req->ends[0]->points = 3;
        $this->recording->complete($matchId, $req);

        $res = $this->summary->getSummary($matchId);

        self::assertNotNull($res);
        self::assertSame(3, $res->scoreA);
        self::assertSame(0, $res->scoreB);
        self::assertSame('A', $res->winner);
        self::assertSame(1, $res->ends);
        self::assertSame('tete_a_tete', $res->type);
        self::assertCount(2, $res->players);

        $rowA = $this->findPlayerRow($res->players, $playerAId);
        self::assertNotNull($rowA);
        self::assertSame(1.0, $rowA->average);
        self::assertSame(1, $rowA->p2);
        self::assertSame(1, $rowA->p0);
        self::assertNotNull($rowA->point);
        self::assertSame(100.0, $rowA->point->successRate);
        self::assertNotNull($rowA->tir);
        self::assertSame(0.0, $rowA->tir->successRate);

        $rowB = $this->findPlayerRow($res->players, $playerBId);
        self::assertNotNull($rowB);
        self::assertNull($rowB->point);
        self::assertNull($rowB->tir);
        self::assertSame([1], $res->endIndexes);
        self::assertCount(1, $rowA->endTotals);
        self::assertSame(1, $rowA->endTotals[0]->endIndex);
        self::assertSame(2, $rowA->endTotals[0]->total);
        self::assertSame([], $rowB->endTotals);
    }

    public function testEndTotalsSumBallNotesPerPlayerIncludingCanceledEnds(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseCompleteRequest($playerAId, $playerBId);
        $req->ends = [
            $this->scoredEnd(1, $playerAId, [1, 0, -2], ['point', 'point', 'tir']),
            $this->scoredEnd(2, $playerAId, [2, 1], ['point', 'tir']),
        ];
        $req->ends[1]->balls[] = (static function (int $playerBId): CompleteMatchEndBallDto {
            $ball = new CompleteMatchEndBallDto();
            $ball->playerId = $playerBId;
            $ball->notes = [-1];
            $ball->shotTypes = ['point'];
            return $ball;
        })($playerBId);

        $this->recording->complete($matchId, $req);

        $res = $this->summary->getSummary($matchId);
        self::assertNotNull($res);
        self::assertSame([1, 2], $res->endIndexes);

        $rowA = $this->findPlayerRow($res->players, $playerAId);
        self::assertNotNull($rowA);
        self::assertSame(-1, $rowA->endTotals[0]->total);
        self::assertSame(3, $rowA->endTotals[1]->total);

        $rowB = $this->findPlayerRow($res->players, $playerBId);
        self::assertNotNull($rowB);
        self::assertCount(1, $rowB->endTotals);
        self::assertSame(2, $rowB->endTotals[0]->endIndex);
        self::assertSame(-1, $rowB->endTotals[0]->total);
    }

    public function testCanceledEndBallsAreIncludedInSummaryAggregatesAndEndGrid(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseCompleteRequest($playerAId, $playerBId);

        $scoredEnd = new CompleteMatchEndDto();
        $scoredEnd->index = 1;
        $scoredEnd->winner = 'A';
        $scoredEnd->points = 2;
        $scoredEnd->canceled = false;
        $ballScored = new CompleteMatchEndBallDto();
        $ballScored->playerId = $playerAId;
        $ballScored->notes = [1];
        $ballScored->shotTypes = ['point'];
        $scoredEnd->balls = [$ballScored];

        $canceledEnd = new CompleteMatchEndDto();
        $canceledEnd->index = 2;
        $canceledEnd->winner = 'A';
        $canceledEnd->points = 0;
        $canceledEnd->canceled = true;
        $ballCanceled = new CompleteMatchEndBallDto();
        $ballCanceled->playerId = $playerAId;
        $ballCanceled->notes = [-2];
        $ballCanceled->shotTypes = ['point'];
        $canceledEnd->balls = [$ballCanceled];

        $req->ends = [$scoredEnd, $canceledEnd];
        $this->recording->complete($matchId, $req);

        $res = $this->summary->getSummary($matchId);
        self::assertNotNull($res);
        self::assertSame(2, $res->scoreA);
        self::assertSame(2, $res->ends);
        self::assertSame([1, 2], $res->endIndexes);
        self::assertSame([2], $res->canceledEndIndexes);

        $rowA = $this->findPlayerRow($res->players, $playerAId);
        self::assertNotNull($rowA);
        self::assertSame(-0.5, $rowA->average);
        self::assertSame(1, $rowA->p1);
        self::assertSame(1, $rowA->m2);
        self::assertCount(2, $rowA->endTotals);
        self::assertSame(1, $rowA->endTotals[0]->endIndex);
        self::assertSame(1, $rowA->endTotals[0]->total);
        self::assertSame(2, $rowA->endTotals[1]->endIndex);
        self::assertSame(-2, $rowA->endTotals[1]->total);
        self::assertNotNull($rowA->point);
        self::assertSame(50.0, $rowA->point->successRate);
        self::assertNull($rowA->tir);
    }

    public function testTieBreakGivesTeamAAsWinner(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseCompleteRequest($playerAId, $playerBId);
        $req->ends[0]->winner = 'B';
        $req->ends[0]->points = 1;
        $this->recording->complete($matchId, $req);

        $end2 = new CompleteMatchEndDto();
        $end2->index = 2;
        $end2->winner = 'A';
        $end2->points = 1;
        $end2->canceled = false;
        $end2->balls = [];
        $req->ends = [$req->ends[0], $end2];
        $this->recording->complete($matchId, $req);

        $res = $this->summary->getSummary($matchId);
        self::assertNotNull($res);
        self::assertSame(1, $res->scoreA);
        self::assertSame(1, $res->scoreB);
        self::assertSame('A', $res->winner);
    }

    public function testSuccessRateIsComputedSeparatelyForPointAndTir(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseCompleteRequest($playerAId, $playerBId);
        $ballB = new CompleteMatchEndBallDto();
        $ballB->playerId = $playerBId;
        $ballB->notes = [2, 2, 0];
        $ballB->shotTypes = ['point', 'tir', 'tir'];
        $req->ends = [
            $this->scoredEnd(1, $playerAId, [2, 1, 1], ['point', 'point', 'point']),
            $this->scoredEnd(2, $playerAId, [0, -1], ['point', 'point']),
            $this->scoredEnd(3, $playerAId, [2, 2, 1], ['tir', 'tir', 'tir']),
            $this->scoredEnd(4, $playerAId, [0, -2], ['tir', 'tir'], [$ballB]),
        ];
        $this->recording->complete($matchId, $req);

        $res = $this->summary->getSummary($matchId);
        self::assertNotNull($res);

        $rowA = $this->findPlayerRow($res->players, $playerAId);
        self::assertNotNull($rowA);
        self::assertNotNull($rowA->point);
        self::assertSame(60.0, $rowA->point->successRate);
        self::assertNotNull($rowA->tir);
        self::assertSame(60.0, $rowA->tir->successRate);

        $rowB = $this->findPlayerRow($res->players, $playerBId);
        self::assertNotNull($rowB);
        self::assertNotNull($rowB->point);
        self::assertSame(100.0, $rowB->point->successRate);
        self::assertNotNull($rowB->tir);
        self::assertSame(50.0, $rowB->tir->successRate);
    }

    public function testUnknownMatchReturnsNull(): void
    {
        self::assertNull($this->summary->getSummary(999999999));
    }

    /**
     * @param list<\App\Dto\Response\MatchSummaryPlayerRow> $rows
     */
    private function findPlayerRow(array $rows, int $playerId): ?\App\Dto\Response\MatchSummaryPlayerRow
    {
        foreach ($rows as $row) {
            if ($row->playerId === $playerId) {
                return $row;
            }
        }

        return null;
    }
}
