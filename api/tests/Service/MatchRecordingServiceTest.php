<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Request\CompleteMatchEndBallDto;
use App\Dto\Request\CompleteMatchEndDto;
use App\Dto\Request\CompleteMatchRequest;
use App\Dto\Request\CreateMatchRequest;
use App\Entity\Player;
use App\Repository\GameBallRepository;
use App\Service\MatchRecordingService;
use App\Service\MatchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Functional tests for the optional per-ball distance (in meters).
 * Services are declared final and cannot be mocked, so we exercise the real
 * services against the test database, like the rest of the test suite.
 */
final class MatchRecordingServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private MatchService $matchService;
    private MatchRecordingService $recording;
    private GameBallRepository $balls;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->matchService = $container->get(MatchService::class);
        $this->recording = $container->get(MatchRecordingService::class);
        $this->balls = $container->get(GameBallRepository::class);
    }

    public function testABallCanBeRecordedWithoutADistance(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseRequest($playerAId, $playerBId);
        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [1];
        $ball->shotTypes = ['point'];
        $ball->distances = [null];
        $req->ends[0]->balls = [$ball];

        $this->recording->complete($matchId, $req);

        $saved = $this->fetchBalls($matchId, $playerAId);
        self::assertCount(1, $saved);
        self::assertNull($saved[0]->getDistance());
    }

    public function testABallCanBeRecordedWithADistance(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseRequest($playerAId, $playerBId);
        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [1, 2];
        $ball->shotTypes = ['point', 'tir'];
        $ball->distances = [7.2, 8.1];
        $req->ends[0]->balls = [$ball];

        $this->recording->complete($matchId, $req);

        $saved = $this->fetchBalls($matchId, $playerAId);
        self::assertCount(2, $saved);
        self::assertSame(7.2, $saved[0]->getDistance());
        self::assertSame(8.1, $saved[1]->getDistance());
    }

    public function testADistanceCanBeMissingForOnlySomeBallsOfTheSamePlayer(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseRequest($playerAId, $playerBId);
        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [1, -1, 2];
        $ball->shotTypes = ['point', 'point', 'tir'];
        $ball->distances = [7.2, null, 6.8];
        $req->ends[0]->balls = [$ball];

        $this->recording->complete($matchId, $req);

        $saved = $this->fetchBalls($matchId, $playerAId);
        self::assertSame(7.2, $saved[0]->getDistance());
        self::assertNull($saved[1]->getDistance());
        self::assertSame(6.8, $saved[2]->getDistance());
    }

    public function testAnEndPartiallyPlayedBeforeBeingClosedOnlyStoresDistancesForPlayedBalls(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseRequest($playerAId, $playerBId);

        $ballA = new CompleteMatchEndBallDto();
        $ballA->playerId = $playerAId;
        $ballA->notes = [1]; // only 1 of 3 balls played
        $ballA->shotTypes = ['point'];
        $ballA->distances = [9.3];

        $ballB = new CompleteMatchEndBallDto();
        $ballB->playerId = $playerBId;
        $ballB->notes = []; // no ball played
        $ballB->shotTypes = [];
        $ballB->distances = [];

        $req->ends[0]->balls = [$ballA, $ballB];

        $this->recording->complete($matchId, $req);

        $savedA = $this->fetchBalls($matchId, $playerAId);
        $savedB = $this->fetchBalls($matchId, $playerBId);
        self::assertCount(1, $savedA);
        self::assertSame(9.3, $savedA[0]->getDistance());
        self::assertCount(0, $savedB);
    }

    public function testACanceledEndKeepsTheDistanceOfBallsAlreadyPlayed(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseRequest($playerAId, $playerBId);
        $req->ends[0]->canceled = true;
        $req->ends[0]->points = 0;

        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [-1];
        $ball->shotTypes = ['point'];
        $ball->distances = [5.0];
        $req->ends[0]->balls = [$ball];

        $this->recording->complete($matchId, $req);

        $saved = $this->fetchBalls($matchId, $playerAId);
        self::assertCount(1, $saved);
        self::assertSame(5.0, $saved[0]->getDistance());
    }

    public function testAnInvalidDistanceIsIgnoredButTheBallIsStillRecorded(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseRequest($playerAId, $playerBId);
        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [1];
        $ball->shotTypes = ['point'];
        $ball->distances = [-3.0]; // invalid, must never block the ball itself
        $req->ends[0]->balls = [$ball];

        $this->recording->complete($matchId, $req);

        $saved = $this->fetchBalls($matchId, $playerAId);
        self::assertCount(1, $saved);
        self::assertSame(1, $saved[0]->getNote());
        self::assertNull($saved[0]->getDistance());
    }

    public function testExistingMatchesWithoutDistancesStillCompleteNormally(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        // Simulate an older client that never sends the `distances` field at all.
        $req = $this->baseRequest($playerAId, $playerBId);
        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [1];
        $ball->shotTypes = ['point'];
        $req->ends[0]->balls = [$ball];

        $this->recording->complete($matchId, $req);

        $saved = $this->fetchBalls($matchId, $playerAId);
        self::assertCount(1, $saved);
        self::assertSame(1, $saved[0]->getNote());
        self::assertNull($saved[0]->getDistance());
    }

    /**
     * @return list<\App\Entity\GameBall>
     */
    private function fetchBalls(int $matchId, int $playerId): array
    {
        $game = $this->em->getRepository(\App\Entity\Game::class)->find($matchId);
        self::assertNotNull($game);

        return $this->balls->createQueryBuilder('b')
            ->join('b.end', 'e')
            ->where('e.game = :g')
            ->andWhere('b.player = :p')
            ->setParameter('g', $game)
            ->setParameter('p', $this->em->getRepository(Player::class)->find($playerId))
            ->orderBy('b.index', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function baseRequest(int $playerAId, int $playerBId): CompleteMatchRequest
    {
        $req = new CompleteMatchRequest();
        $req->type = 'tete_a_tete';
        $req->targetScore = 13;
        $req->statisticsMode = 'standard';
        $req->teamA = [$playerAId];
        $req->teamB = [$playerBId];
        $req->trackedPlayers = [$playerAId, $playerBId];

        $end = new CompleteMatchEndDto();
        $end->index = 1;
        $end->winner = 'A';
        $end->points = 1;
        $end->canceled = false;
        $end->balls = [];
        $req->ends = [$end];

        return $req;
    }

    /**
     * @return array{0:int,1:int,2:int} matchId, playerAId, playerBId
     */
    private function createHeadToHead(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $playerA = new Player('Alice', 'Test'.$suffix, 'Ali'.$suffix);
        $playerB = new Player('Bob', 'Test'.$suffix, 'Bob'.$suffix);
        $this->em->persist($playerA);
        $this->em->persist($playerB);
        $this->em->flush();

        $createReq = new CreateMatchRequest();
        $createReq->type = 'tete_a_tete';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = [$playerA->getId()];
        $createReq->teamB = [$playerB->getId()];
        $createReq->trackedPlayers = [$playerA->getId(), $playerB->getId()];

        $created = $this->matchService->create($createReq);

        return [$created->id, (int) $playerA->getId(), (int) $playerB->getId()];
    }
}
