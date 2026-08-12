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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MatchSummaryServiceTest extends KernelTestCase
{
    use MatchTestHelpers;

    private MatchSummaryService $summary;

    protected function setUp(): void
    {
        self::bootKernel();
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
        self::assertCount(2, $res->players);

        $rowA = $this->findPlayerRow($res->players, $playerAId);
        self::assertNotNull($rowA);
        self::assertSame(1.0, $rowA->average);
        self::assertSame(1, $rowA->p2);
        self::assertSame(1, $rowA->p0);
    }

    public function testCanceledEndBallsAreExcludedFromSummaryAggregates(): void
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

        $rowA = $this->findPlayerRow($res->players, $playerAId);
        self::assertNotNull($rowA);
        self::assertSame(1.0, $rowA->average);
        self::assertSame(1, $rowA->p1);
        self::assertSame(0, $rowA->m2);
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
