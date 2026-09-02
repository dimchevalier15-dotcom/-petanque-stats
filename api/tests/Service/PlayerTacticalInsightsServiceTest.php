<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\PlayerTacticalInsightsService;
use App\Tests\Support\KernelDatabaseTestCase;
use App\Tests\Support\MatchTestHelpers;
use Doctrine\ORM\EntityManagerInterface;

final class PlayerTacticalInsightsServiceTest extends KernelDatabaseTestCase
{
    use MatchTestHelpers;

    private PlayerTacticalInsightsService $insights;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->matchService = $container->get(\App\Service\MatchService::class);
        $this->recording = $container->get(\App\Service\MatchRecordingService::class);
        $this->insights = $container->get(PlayerTacticalInsightsService::class);
        $this->jwtEncoder = $container->get(\Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface::class);
    }

    public function testAggregatesPlayerMarkingAndRajoutByDistance(): void
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
        $playerB2 = $teamB[1];

        $createReq = new \App\Dto\Request\CreateMatchRequest();
        $createReq->type = 'doublette';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = $teamA;
        $createReq->teamB = $teamB;
        $createReq->trackedPlayers = array_merge($teamA, $teamB);

        $matchId = $this->matchService->create($createReq, $owner)->id;

        $req = $this->baseCompleteRequest($teamA[0], $teamB[0]);
        $req->type = 'doublette';
        $req->teamA = $teamA;
        $req->teamB = $teamB;
        $req->trackedPlayers = array_merge($teamA, $teamB);

        $end = new \App\Dto\Request\CompleteMatchEndDto();
        $end->index = 1;
        $end->winner = 'B';
        $end->points = 2;
        $end->canceled = false;
        $end->shots = [];

        $sequenceOrder = 1;
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $teamA[0], 0, 'point');
        }
        for ($i = 0; $i < 2; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $teamA[1], 0, 'point');
        }
        $end->shots[] = $this->shotDto($sequenceOrder++, $teamA[1], 1, 'point');
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $teamB[0], 0, 'point');
        }
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, -1, 'tir', 7.5);
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, 1, 'tir', 7.5);
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, 2, 'tir', 7.8);

        $req->ends = [$end];
        $this->recording->complete($matchId, $req);

        $res = $this->insights->insightsForPlayerId($playerB2);

        self::assertSame('ok', $res->status);
        self::assertNotNull($res->markingOverall);
        self::assertSame(2, $res->markingOverall->tir->attempts);
        self::assertSame(1, $res->markingOverall->tir->made);
        self::assertNotNull($res->rajoutOverall);
        self::assertSame(1, $res->rajoutOverall->tir->attempts);
        self::assertSame(1, $res->rajoutOverall->tir->made);
        self::assertNotEmpty($res->markingByDistance);
        self::assertNotEmpty($res->rajoutByDistance);
    }

    public function testPlayerRajoutStopsAfterMinusTwo(): void
    {
        [$matchId, $teamA, $teamB, $playerB2] = $this->createDoubletteMatch();

        $end = new \App\Dto\Request\CompleteMatchEndDto();
        $end->index = 1;
        $end->winner = 'B';
        $end->points = 2;
        $end->canceled = false;
        $end->shots = [];

        $sequenceOrder = 1;
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $teamA[0], 0, 'point');
        }
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $teamA[1], 0, 'point');
        }
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $teamB[0], 0, 'point');
        }
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, -1, 'tir');
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, -2, 'tir');
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, 2, 'tir');

        $this->completeDoubletteEnd($matchId, $teamA, $teamB, $end);

        $res = $this->insights->insightsForPlayerId($playerB2);

        self::assertSame('ok', $res->status);
        self::assertNotNull($res->rajoutOverall);
        self::assertSame(2, $res->rajoutOverall->tir->attempts);
        self::assertSame(0, $res->rajoutOverall->tir->made);
        self::assertSame(0.0, $res->rajoutOverall->tir->rate);
    }

    public function testPlayerRajoutAggregatesOnlyOwnShots(): void
    {
        [$matchId, $teamA, $teamB, $playerB2] = $this->createDoubletteMatch();

        $end = new \App\Dto\Request\CompleteMatchEndDto();
        $end->index = 1;
        $end->winner = 'B';
        $end->points = 2;
        $end->canceled = false;
        $end->shots = [];

        $sequenceOrder = 1;
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $teamA[0], 0, 'point');
        }
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $teamA[1], 0, 'point');
        }
        for ($i = 0; $i < 3; $i++) {
            $end->shots[] = $this->shotDto($sequenceOrder++, $teamB[0], 0, 'point');
        }
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, -1, 'tir');
        $end->shots[] = $this->shotDto($sequenceOrder++, $playerB2, 1, 'tir');

        $this->completeDoubletteEnd($matchId, $teamA, $teamB, $end);

        $resB1 = $this->insights->insightsForPlayerId($teamB[0]);
        $resB2 = $this->insights->insightsForPlayerId($playerB2);

        self::assertSame('ok', $resB1->status);
        self::assertNotNull($resB1->rajoutOverall);
        self::assertSame(3, $resB1->rajoutOverall->point->attempts);
        self::assertSame(0, $resB1->rajoutOverall->point->made);
        self::assertSame(0, $resB1->rajoutOverall->tir->attempts);

        self::assertSame('ok', $resB2->status);
        self::assertNotNull($resB2->rajoutOverall);
        self::assertSame(0, $resB2->rajoutOverall->point->attempts);
        self::assertSame(2, $resB2->rajoutOverall->tir->attempts);
        self::assertSame(1, $resB2->rajoutOverall->tir->made);
        self::assertSame(50.0, $resB2->rajoutOverall->tir->rate);
    }

    /**
     * @return array{0:int,1:list<int>,2:list<int>,3:int} matchId, teamA, teamB, playerB2
     */
    private function createDoubletteMatch(): array
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

        $createReq = new \App\Dto\Request\CreateMatchRequest();
        $createReq->type = 'doublette';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = $teamA;
        $createReq->teamB = $teamB;
        $createReq->trackedPlayers = array_merge($teamA, $teamB);

        $matchId = $this->matchService->create($createReq, $owner)->id;

        return [$matchId, $teamA, $teamB, $teamB[1]];
    }

    /**
     * @param list<int> $teamA
     * @param list<int> $teamB
     */
    private function completeDoubletteEnd(int $matchId, array $teamA, array $teamB, \App\Dto\Request\CompleteMatchEndDto $end): void
    {
        $req = $this->baseCompleteRequest($teamA[0], $teamB[0]);
        $req->type = 'doublette';
        $req->teamA = $teamA;
        $req->teamB = $teamB;
        $req->trackedPlayers = array_merge($teamA, $teamB);
        $req->ends = [$end];
        $this->recording->complete($matchId, $req);
    }

    private function shotDto(int $sequenceOrder, int $playerId, int $note, string $shotType, ?float $distance = null): \App\Dto\Request\CompleteMatchEndShotDto
    {
        $shot = new \App\Dto\Request\CompleteMatchEndShotDto();
        $shot->sequenceOrder = $sequenceOrder;
        $shot->playerId = $playerId;
        $shot->note = $note;
        $shot->shotType = $shotType;
        $shot->distance = $distance;

        return $shot;
    }
}
