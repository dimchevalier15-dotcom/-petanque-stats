<?php

declare(strict_types=1);

namespace App\Tests\Service\Training;

use App\Dto\Request\CreateTrainingSessionRequest;
use App\Dto\Request\RecordTrainingAttemptRequest;
use App\Dto\Response\RecordTrainingAttemptResponse;
use App\Entity\Player;
use App\Entity\User;
use App\Service\Training\InvalidTrainingAttemptException;
use App\Service\Training\NoLinkedPlayerException;
use App\Service\Training\TrainingSessionAccessDeniedException;
use App\Service\Training\TrainingSessionAlreadyFinishedException;
use App\Service\Training\TrainingSessionNotFoundException;
use App\Service\Training\TrainingSessionService;
use App\Service\Training\TrainingStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use App\Tests\Support\KernelDatabaseTestCase;

final class TrainingSessionServiceTest extends KernelDatabaseTestCase
{
    private EntityManagerInterface $em;
    private TrainingSessionService $service;
    private TrainingStatsService $statsService;
    private JWTEncoderInterface $jwtEncoder;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(TrainingSessionService::class);
        $this->statsService = $container->get(TrainingStatsService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
    }

    public function testCreateSessionForLinkedPlayer(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 10;

        $started = $this->service->create($token, $req);

        self::assertSame('point', $started->type);
        self::assertSame(7.0, $started->distance);
        self::assertSame(10, $started->plannedBalls);
        self::assertSame(0, $started->attemptsCount);
    }

    public function testCreateFailsWithoutLinkedPlayer(): void
    {
        $token = $this->createUserWithoutLinkedPlayer();
        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 5;

        $this->expectException(NoLinkedPlayerException::class);
        $this->service->create($token, $req);
    }

    public function testPlayerCanCreateMultipleSessions(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 5;

        $first = $this->service->create($token, $req);
        $this->completeSession($token, $first->id, 5, 'perfect');

        $req->type = 'tir';
        $second = $this->service->create($token, $req);
        $this->completeSession($token, $second->id, 5, 'successful');

        $history = $this->service->history($token, 1, 20);
        self::assertSame(2, $history->total);
    }

    public function testEachAttemptIsRecordedIndividually(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 8.0;
        $req->plannedBalls = 3;

        $session = $this->service->create($token, $req);
        $this->recordAttempt($token, $session->id, 'perfect');
        $this->recordAttempt($token, $session->id, 'bad');

        $summary = $this->service->getSummary($token, $session->id);
        self::assertCount(2, $summary->attempts);
        self::assertSame(1, $summary->attempts[0]->number);
        self::assertSame('perfect', $summary->attempts[0]->result);
        self::assertSame(2, $summary->attempts[0]->score);
    }

    public function testScoreIsComputedFromAttempts(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 3;

        $session = $this->service->create($token, $req);
        $this->recordAttempt($token, $session->id, 'perfect');
        $this->recordAttempt($token, $session->id, 'perfect');
        $res = $this->recordAttempt($token, $session->id, 'bad');

        self::assertTrue($res->sessionFinished);
        self::assertNotNull($res->summary);
        self::assertSame(3, $res->summary->totalScore);
        self::assertSame(2, $res->summary->successfulBalls);
    }

    public function testPartialSessionKeepsRecordedAttempts(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 10;

        $session = $this->service->create($token, $req);
        $this->recordAttempt($token, $session->id, 'perfect');
        $this->recordAttempt($token, $session->id, 'perfect');

        $summary = $this->service->getSummary($token, $session->id);
        self::assertCount(2, $summary->attempts);
        self::assertNull($summary->finishedAt);
    }

    public function testInProgressSessionCanBeResumed(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 5;

        $session = $this->service->create($token, $req);
        $this->recordAttempt($token, $session->id, 'perfect');

        $current = $this->service->current($token);
        self::assertNotNull($current);
        self::assertSame($session->id, $current->id);
        self::assertSame(1, $current->attemptsCount);
    }

    public function testAnotherUserCannotAccessSession(): void
    {
        [$ownerToken] = $this->createUserWithLinkedPlayer();
        [$otherToken] = $this->createUserWithLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 5;

        $session = $this->service->create($ownerToken, $req);

        $this->expectException(TrainingSessionAccessDeniedException::class);
        $this->service->getSummary($otherToken, $session->id);
    }

    public function testStatsOnlyIncludeTrainingData(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 2;

        $session = $this->service->create($token, $req);
        $this->recordAttempt($token, $session->id, 'perfect');
        $this->recordAttempt($token, $session->id, 'perfect');

        $stats = $this->statsService->stats($token);
        self::assertSame('ok', $stats->status);
        self::assertSame(1, $stats->summary->sessionsCount);
        self::assertSame(2, $stats->summary->totalBalls);
        self::assertSame(2, $stats->summary->successfulBalls);
        self::assertSame(100.0, $stats->summary->successRate);
    }

    public function testStatsAreIsolatedPerUser(): void
    {
        [$tokenA] = $this->createUserWithLinkedPlayer();
        [$tokenB] = $this->createUserWithLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 1;

        $session = $this->service->create($tokenA, $req);
        $this->recordAttempt($tokenA, $session->id, 'perfect');

        $statsB = $this->statsService->stats($tokenB);
        self::assertSame('no_sessions', $statsB->status);
    }

    public function testStatsCanBeLoadedForImpersonatedPlayer(): void
    {
        [$token, $player] = $this->createUserWithLinkedPlayer();
        $playerId = (int) $player->getId();
        $coachToken = $this->createUserWithoutLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 2;

        $session = $this->service->create($token, $req);
        $this->recordAttempt($token, $session->id, 'perfect');
        $this->recordAttempt($token, $session->id, 'perfect');

        $stats = $this->statsService->stats($coachToken, null, null, $playerId);
        self::assertSame('ok', $stats->status);
        self::assertSame(1, $stats->summary->sessionsCount);
        self::assertSame(2, $stats->summary->totalBalls);
    }

    public function testHistoryCanBeLoadedForImpersonatedPlayer(): void
    {
        [$token, $player] = $this->createUserWithLinkedPlayer();
        $playerId = (int) $player->getId();
        $coachToken = $this->createUserWithoutLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 1;

        $session = $this->service->create($token, $req);
        $this->recordAttempt($token, $session->id, 'perfect');

        $history = $this->service->history($coachToken, 1, 20, $playerId);
        self::assertSame(1, $history->total);
        self::assertSame($session->id, $history->items[0]->id);
    }

    public function testSummaryCanBeLoadedForImpersonatedPlayer(): void
    {
        [$token, $player] = $this->createUserWithLinkedPlayer();
        $playerId = (int) $player->getId();
        $coachToken = $this->createUserWithoutLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 1;

        $session = $this->service->create($token, $req);
        $this->recordAttempt($token, $session->id, 'perfect');

        $summary = $this->service->getSummary($coachToken, $session->id, $playerId);
        self::assertSame($session->id, $summary->id);
        self::assertSame(2, $summary->totalScore);
    }

    public function testInvalidResultIsRejected(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 5;

        $session = $this->service->create($token, $req);

        $attemptReq = new RecordTrainingAttemptRequest();
        $attemptReq->result = 'carreau';

        $this->expectException(InvalidTrainingAttemptException::class);
        $this->service->recordAttempt($token, $session->id, $attemptReq);
    }

    public function testAbandonRemovesInProgressSession(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $req = new CreateTrainingSessionRequest();
        $req->type = 'point';
        $req->distance = 7.0;
        $req->plannedBalls = 5;

        $session = $this->service->create($token, $req);
        $this->service->abandon($token, $session->id);

        self::assertNull($this->service->current($token));
        $this->expectException(TrainingSessionNotFoundException::class);
        $this->service->getSummary($token, $session->id);
    }

    /**
     * @return array{0:string,1:Player}
     */
    private function createUserWithLinkedPlayer(): array
    {
        $email = sprintf('training-%s@example.test', bin2hex(random_bytes(6)));
        $user = new User($email);
        $this->em->persist($user);
        $this->em->flush();

        $player = new Player('Jean', 'Bernard', 'Jeannot');
        $player->setUser($user);
        $this->em->persist($player);
        $this->em->flush();

        $token = $this->jwtEncoder->encode(['username' => $email, 'sub' => (string) $user->getId()]);

        return [$token, $player];
    }

    private function createUserWithoutLinkedPlayer(): string
    {
        $email = sprintf('training-%s@example.test', bin2hex(random_bytes(6)));
        $user = new User($email);
        $this->em->persist($user);
        $this->em->flush();

        return $this->jwtEncoder->encode(['username' => $email, 'sub' => (string) $user->getId()]);
    }

    private function recordAttempt(string $token, int $sessionId, string $result): RecordTrainingAttemptResponse
    {
        $attemptReq = new RecordTrainingAttemptRequest();
        $attemptReq->result = $result;
        return $this->service->recordAttempt($token, $sessionId, $attemptReq);
    }

    private function completeSession(string $token, int $sessionId, int $balls, string $result): void
    {
        for ($i = 0; $i < $balls; $i++) {
            $this->recordAttempt($token, $sessionId, $result);
        }
    }
}
