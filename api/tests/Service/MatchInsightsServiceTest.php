<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Request\CompleteMatchEndDto;
use App\Dto\Request\CompleteMatchEndShotDto;
use App\Dto\Request\CompleteMatchRequest;
use App\Service\MatchInsightsService;
use App\Tests\Support\KernelDatabaseTestCase;
use App\Tests\Support\MatchTestHelpers;
use Doctrine\ORM\EntityManagerInterface;

final class MatchInsightsServiceTest extends KernelDatabaseTestCase
{
    use MatchTestHelpers;

    private MatchInsightsService $insights;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->matchService = $container->get(\App\Service\MatchService::class);
        $this->recording = $container->get(\App\Service\MatchRecordingService::class);
        $this->insights = $container->get(MatchInsightsService::class);
        $this->jwtEncoder = $container->get(\Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface::class);
    }

    public function testInsightsUnavailableWhenNotAllPlayersTracked(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseCompleteRequest($playerAId, $playerBId);
        $req->trackedPlayers = [$playerAId];
        $req->ends = [$this->endWithShots($playerAId, $playerBId, [$playerAId, $playerBId])];
        $this->recording->complete($matchId, $req);

        $res = $this->insights->getInsights($matchId);

        self::assertNotNull($res);
        self::assertSame('unavailable', $res->status);
        self::assertSame('not_all_tracked', $res->reason);
    }

    public function testInsightsAvailableWithGlobalSequence(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseCompleteRequest($playerAId, $playerBId);
        $req->ends = [$this->endWithShots($playerAId, $playerBId, [$playerAId, $playerBId])];
        $this->recording->complete($matchId, $req);

        $res = $this->insights->getInsights($matchId);

        self::assertNotNull($res);
        self::assertSame('ok', $res->status);
        self::assertNotNull($res->teamA);
        self::assertNotNull($res->teamB);
        self::assertSame(1, $res->teamA->endsOpened);
        self::assertSame(0, $res->markingTeamA?->point->attempts ?? 0);
        self::assertSame(0, $res->markingTeamB?->tir->attempts ?? 0);
    }

    public function testMarkingWhenOpponentIsOutUntilFirstSuccess(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $owner = new \App\Entity\User('owner'.$suffix.'@test.local');
        $owner->setPassword('hash');
        $this->em->persist($owner);

        $players = [];
        for ($i = 0; $i < 4; $i++) {
            $p = new \App\Entity\Player('P'.$i, 'Test'.$suffix, 'P'.$i);
            $this->em->persist($p);
            $players[] = $p;
        }
        $this->em->flush();

        $teamA = [(int) $players[0]->getId(), (int) $players[1]->getId()];
        $teamB = [(int) $players[2]->getId(), (int) $players[3]->getId()];
        $playerA1 = $teamA[0];
        $playerA2 = $teamA[1];
        $playerB1 = $teamB[0];
        $playerB2 = $teamB[1];

        $createReq = new \App\Dto\Request\CreateMatchRequest();
        $createReq->type = 'doublette';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = $teamA;
        $createReq->teamB = $teamB;
        $createReq->trackedPlayers = array_merge($teamA, $teamB);

        $matchId = $this->matchService->create($createReq, $owner)->id;

        $req = new CompleteMatchRequest();
        $req->type = 'doublette';
        $req->targetScore = 13;
        $req->statisticsMode = 'standard';
        $req->teamA = $teamA;
        $req->teamB = $teamB;
        $req->trackedPlayers = array_merge($teamA, $teamB);

        $end = new CompleteMatchEndDto();
        $end->index = 1;
        $end->winner = 'B';
        $end->points = 2;
        $end->canceled = false;
        $end->shots = [];

        $sequenceOrder = 1;
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $playerA1, 0, 'point');
        }
        for ($i = 0; $i < 2; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $playerA2, 0, 'point');
        }
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerA2, 1, 'point');
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $playerB1, 0, 'point');
        }
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, -1, 'tir');
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, 1, 'tir');
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, 2, 'tir');

        $req->ends = [$end];
        $this->recording->complete($matchId, $req);

        $res = $this->insights->getInsights($matchId);

        self::assertNotNull($res);
        self::assertSame('ok', $res->status);
        self::assertNotNull($res->markingTeamB);
        self::assertSame(2, $res->markingTeamB->tir->attempts);
        self::assertSame(1, $res->markingTeamB->tir->made);
        self::assertSame(50.0, $res->markingTeamB->tir->rate);
        self::assertSame(0, $res->markingTeamA?->point->attempts ?? 0);
        self::assertNotNull($res->rajoutTeamB);
        self::assertSame(1, $res->rajoutTeamB->tir->attempts);
        self::assertSame(1, $res->rajoutTeamB->tir->made);
    }

    public function testRajoutWhenOpponentLastBallIsNotPositive(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $owner = new \App\Entity\User('owner'.$suffix.'@test.local');
        $owner->setPassword('hash');
        $this->em->persist($owner);

        $players = [];
        for ($i = 0; $i < 4; $i++) {
            $p = new \App\Entity\Player('P'.$i, 'Test'.$suffix, 'P'.$i);
            $this->em->persist($p);
            $players[] = $p;
        }
        $this->em->flush();

        $teamA = [(int) $players[0]->getId(), (int) $players[1]->getId()];
        $teamB = [(int) $players[2]->getId(), (int) $players[3]->getId()];
        $playerA1 = $teamA[0];
        $playerA2 = $teamA[1];
        $playerB1 = $teamB[0];
        $playerB2 = $teamB[1];

        $createReq = new \App\Dto\Request\CreateMatchRequest();
        $createReq->type = 'doublette';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = $teamA;
        $createReq->teamB = $teamB;
        $createReq->trackedPlayers = array_merge($teamA, $teamB);

        $matchId = $this->matchService->create($createReq, $owner)->id;

        $req = new CompleteMatchRequest();
        $req->type = 'doublette';
        $req->targetScore = 13;
        $req->statisticsMode = 'standard';
        $req->teamA = $teamA;
        $req->teamB = $teamB;
        $req->trackedPlayers = array_merge($teamA, $teamB);

        $end = new CompleteMatchEndDto();
        $end->index = 1;
        $end->winner = 'B';
        $end->points = 2;
        $end->canceled = false;
        $end->shots = [];

        $sequenceOrder = 1;
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $playerA1, 0, 'point');
        }
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $playerA2, 0, 'point');
        }
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $playerB1, 0, 'point');
        }
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, -1, 'tir');
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, 1, 'tir');
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, 2, 'tir');

        $req->ends = [$end];
        $this->recording->complete($matchId, $req);

        $res = $this->insights->getInsights($matchId);

        self::assertNotNull($res);
        self::assertSame('ok', $res->status);
        self::assertSame(0, $res->markingTeamB?->tir->attempts ?? 0);
        self::assertNotNull($res->rajoutTeamB);
        self::assertSame(3, $res->rajoutTeamB->point->attempts);
        self::assertSame(3, $res->rajoutTeamB->tir->attempts);
        self::assertSame(2, $res->rajoutTeamB->tir->made);
        self::assertSame(66.7, $res->rajoutTeamB->tir->rate);
    }

    private function shotDto(int $sequenceOrder, int $playerId, int $note, string $shotType): CompleteMatchEndShotDto
    {
        $shot = new CompleteMatchEndShotDto();
        $shot->sequenceOrder = $sequenceOrder;
        $shot->playerId = $playerId;
        $shot->note = $note;
        $shot->shotType = $shotType;

        return $shot;
    }

    /**
     * @param list<int> $sequence Player ids in shot order
     */
    private function endWithShots(int $playerAId, int $playerBId, array $sequence): CompleteMatchEndDto
    {
        $end = new CompleteMatchEndDto();
        $end->index = 1;
        $end->winner = 'A';
        $end->points = 2;
        $end->canceled = false;
        $end->shots = [];

        foreach ($sequence as $index => $playerId) {
            $shot = new CompleteMatchEndShotDto();
            $shot->sequenceOrder = $index + 1;
            $shot->playerId = $playerId;
            $shot->note = $playerId === $playerAId ? 1 : -1;
            $shot->shotType = $playerId === $playerAId ? 'point' : 'tir';
            $shot->distance = 7.5;
            $end->shots[] = $shot;
        }

        return $end;
    }
}
