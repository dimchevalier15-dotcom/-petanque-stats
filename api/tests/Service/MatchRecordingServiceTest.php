<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Request\CompleteMatchEndBallDto;
use App\Dto\Request\CompleteMatchEndDto;
use App\Dto\Request\CompleteMatchRequest;
use App\Dto\Request\CreateMatchRequest;
use App\Entity\Player;
use App\Entity\User;
use App\Repository\GameBallRepository;
use App\Service\MatchRecordingService;
use App\Service\MatchService;
use App\Tests\Support\MatchTestHelpers;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Support\KernelDatabaseTestCase;

/**
 * Functional tests for the optional per-ball distance (in meters).
 * Services are declared final and cannot be mocked, so we exercise the real
 * services against the test database, like the rest of the test suite.
 */
final class MatchRecordingServiceTest extends KernelDatabaseTestCase
{
    use MatchTestHelpers;

    private GameBallRepository $balls;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->matchService = $container->get(MatchService::class);
        $this->recording = $container->get(MatchRecordingService::class);
        $this->balls = $container->get(GameBallRepository::class);
        $this->jwtEncoder = $container->get(\Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface::class);
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

    public function testCompletingTheSameMatchTwiceDoesNotDuplicateEndsOrBalls(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseRequest($playerAId, $playerBId);
        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [1, 0];
        $ball->shotTypes = ['point', 'tir'];
        $ball->distances = [7.0, 8.0];
        $req->ends[0]->balls = [$ball];

        $this->recording->complete($matchId, $req);
        $this->recording->complete($matchId, $req);

        $game = $this->em->getRepository(\App\Entity\Game::class)->find($matchId);
        self::assertNotNull($game);

        $endCount = $this->em->getRepository(\App\Entity\GameEnd::class)->count(['game' => $game]);
        self::assertSame(1, $endCount);

        $saved = $this->fetchBalls($matchId, $playerAId);
        self::assertCount(2, $saved);
        self::assertSame(1, $saved[0]->getNote());
        self::assertSame(0, $saved[1]->getNote());
        self::assertSame(7.0, $saved[0]->getDistance());
        self::assertSame(8.0, $saved[1]->getDistance());
    }

    public function testTripletteCapsBallsAtTwoPerPlayerEvenWhenThreeNotesAreSent(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $owner = new User('owner'.$suffix.'@test.local');
        $owner->setPassword('hash');
        $this->em->persist($owner);

        $players = [];
        for ($i = 0; $i < 6; $i++) {
            $p = new Player('P'.$i, 'Test'.$suffix, 'P'.$i);
            $this->em->persist($p);
            $players[] = $p;
        }
        $this->em->flush();

        $teamA = array_map(static fn (Player $p): int => (int) $p->getId(), array_slice($players, 0, 3));
        $teamB = array_map(static fn (Player $p): int => (int) $p->getId(), array_slice($players, 3, 3));
        $tracked = array_merge($teamA, $teamB);

        $createReq = new CreateMatchRequest();
        $createReq->type = 'triplette';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = $teamA;
        $createReq->teamB = $teamB;
        $createReq->trackedPlayers = $tracked;

        $matchId = $this->matchService->create($createReq, $owner)->id;
        $playerAId = $teamA[0];

        $req = new CompleteMatchRequest();
        $req->type = 'triplette';
        $req->targetScore = 13;
        $req->statisticsMode = 'standard';
        $req->teamA = $teamA;
        $req->teamB = $teamB;
        $req->trackedPlayers = $tracked;

        $end = new CompleteMatchEndDto();
        $end->index = 1;
        $end->winner = 'A';
        $end->points = 2;
        $end->canceled = false;

        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [1, 0, 2];
        $ball->shotTypes = ['point', 'point', 'tir'];
        $end->balls = [$ball];
        $req->ends = [$end];

        $this->recording->complete($matchId, $req);

        $saved = $this->fetchBalls($matchId, $playerAId);
        self::assertCount(2, $saved);
        self::assertSame(1, $saved[0]->getNote());
        self::assertSame(0, $saved[1]->getNote());
    }

    public function testInvalidNotesAreSkipped(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseRequest($playerAId, $playerBId);
        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [3, -3, 1];
        $ball->shotTypes = ['point', 'point', 'point'];
        $req->ends[0]->balls = [$ball];

        $this->recording->complete($matchId, $req);

        $saved = $this->fetchBalls($matchId, $playerAId);
        self::assertCount(1, $saved);
        self::assertSame(1, $saved[0]->getNote());
    }

    public function testHeadToHeadPointsAreCappedAtThree(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseRequest($playerAId, $playerBId);
        $req->ends[0]->points = 9;

        $this->recording->complete($matchId, $req);

        $game = $this->em->getRepository(\App\Entity\Game::class)->find($matchId);
        self::assertNotNull($game);
        $end = $this->em->getRepository(\App\Entity\GameEnd::class)->findOneBy(['game' => $game]);
        self::assertNotNull($end);
        self::assertSame(3, $end->getPoints());
    }

    public function testANonCanceledEndWithZeroPointsIsPersisted(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseRequest($playerAId, $playerBId);
        $req->ends[0]->points = 0;
        $req->ends[0]->canceled = false;

        $this->recording->complete($matchId, $req);

        $game = $this->em->getRepository(\App\Entity\Game::class)->find($matchId);
        self::assertNotNull($game);
        $end = $this->em->getRepository(\App\Entity\GameEnd::class)->findOneBy(['game' => $game]);
        self::assertNotNull($end);
        self::assertFalse($end->isCanceled());
        self::assertSame(0, $end->getPoints());
    }

    public function testEndPlayerRolesArePersisted(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseRequest($playerAId, $playerBId);
        $roleA = new \App\Dto\Request\CompleteMatchEndRoleDto();
        $roleA->playerId = $playerAId;
        $roleA->role = 'pointeur';
        $roleB = new \App\Dto\Request\CompleteMatchEndRoleDto();
        $roleB->playerId = $playerBId;
        $roleB->role = 'tireur';
        $req->ends[0]->roles = [$roleA, $roleB];

        $this->recording->complete($matchId, $req);

        $game = $this->em->getRepository(\App\Entity\Game::class)->find($matchId);
        self::assertNotNull($game);
        $end = $this->em->getRepository(\App\Entity\GameEnd::class)->findOneBy(['game' => $game]);
        self::assertNotNull($end);

        $roles = $this->em->getRepository(\App\Entity\GameEndPlayerRole::class)->findBy(['end' => $end]);
        self::assertCount(2, $roles);
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
            ->orderBy('b.sequenceOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function baseRequest(int $playerAId, int $playerBId): CompleteMatchRequest
    {
        return $this->baseCompleteRequest($playerAId, $playerBId);
    }
}
