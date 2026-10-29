<?php

declare(strict_types=1);

namespace App\Tests\Service\JumpShot;

use App\Dto\Request\JumpShotThrowInputDto;
use App\Dto\Request\StartJumpShotSessionRequest;
use App\Dto\Request\SubmitJumpShotSeriesRequest;
use App\Entity\Player;
use App\Entity\User;
use App\Service\JumpShot\InvalidJumpShotSeriesException;
use App\Service\JumpShot\JumpShotSessionAlreadyFinishedException;
use App\Service\JumpShot\JumpShotSessionService;
use App\Service\JumpShot\NoLinkedPlayerException;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use App\Tests\Support\KernelDatabaseTestCase;

final class JumpShotSessionServiceTest extends KernelDatabaseTestCase
{
    private EntityManagerInterface $em;
    private JumpShotSessionService $service;
    private JWTEncoderInterface $jwtEncoder;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(JumpShotSessionService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
    }

    public function testStartSessionAtSixMeters(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $started = $this->service->start($token);

        self::assertSame(6, $started->currentDistance);
        self::assertSame(0, $started->seriesCount);
        self::assertSame('standard', $started->progressionMode);
    }

    public function testStartSimplifiedSession(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $req = new StartJumpShotSessionRequest();
        $req->progressionMode = 'simplified';
        $started = $this->service->start($token, $req);

        self::assertSame('simplified', $started->progressionMode);
    }

    public function testSimplifiedProgressionTwoOfThreeMovesUp(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $req = new StartJumpShotSessionRequest();
        $req->progressionMode = 'simplified';
        $started = $this->service->start($token, $req);

        $res = $this->service->submitSeries($token, $started->id, $this->seriesRequest([
            1 => 'hit',
            2 => 'carreau',
            3 => 'missed',
        ]));

        self::assertSame('up', $res->series->consequence);
        self::assertSame(7, $res->nextDistance);
    }

    public function testSubmitSeriesPersistsThrowsAndProgression(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $res = $this->service->submitSeries($token, $started->id, $this->seriesRequest([
            1 => 'chique',
            2 => 'hit',
            3 => 'carreau',
        ]));

        self::assertSame(3, $res->series->successCount);
        self::assertSame('up', $res->series->consequence);
        self::assertSame(7, $res->nextDistance);
        self::assertCount(3, $res->series->throws);
        self::assertSame('chique', $res->series->throws[0]->result);
    }

    public function testCarreauSalvateurPersisted(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $res = $this->service->submitSeries($token, $started->id, $this->seriesRequest([
            1 => 'missed',
            2 => 'missed',
            3 => 'carreau',
        ]));

        self::assertTrue($res->series->isSpecialSeries);
        self::assertSame(1, $res->series->successCount);
        self::assertSame('stay', $res->series->consequence);
        self::assertSame(6, $res->nextDistance);
    }

    public function testVictoryAtNine(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $sessionId = $started->id;
        $this->service->submitSeries($token, $sessionId, $this->seriesRequest(['chique', 'hit', 'carreau']));
        $this->service->submitSeries($token, $sessionId, $this->seriesRequest(['chique', 'hit', 'carreau']));
        $this->service->submitSeries($token, $sessionId, $this->seriesRequest(['chique', 'hit', 'carreau']));
        $won = $this->service->submitSeries($token, $sessionId, $this->seriesRequest(['chique', 'hit', 'carreau']));

        self::assertTrue($won->sessionWon);
        self::assertTrue($won->sessionFinished);
        self::assertTrue($won->session->won);
    }

    public function testCannotSubmitAfterVictory(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);
        $sessionId = $started->id;

        for ($i = 0; $i < 4; ++$i) {
            $this->service->submitSeries($token, $sessionId, $this->seriesRequest(['chique', 'hit', 'carreau']));
        }

        $this->expectException(JumpShotSessionAlreadyFinishedException::class);
        $this->service->submitSeries($token, $sessionId, $this->seriesRequest(['chique', 'hit', 'carreau']));
    }

    public function testIncompleteSeriesRejected(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $req = new SubmitJumpShotSeriesRequest();
        $throw = new JumpShotThrowInputDto();
        $throw->position = 1;
        $throw->result = 'missed';
        $req->throws = [$throw];

        $this->expectException(InvalidJumpShotSeriesException::class);
        $this->service->submitSeries($token, $started->id, $req);
    }

    public function testDuplicatePositionsRejected(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $req = new SubmitJumpShotSeriesRequest();
        foreach (['missed', 'missed', 'missed'] as $result) {
            $throw = new JumpShotThrowInputDto();
            $throw->position = 1;
            $throw->result = $result;
            $req->throws[] = $throw;
        }

        $this->expectException(InvalidJumpShotSeriesException::class);
        $this->service->submitSeries($token, $started->id, $req);
    }

    public function testResumeCurrentSession(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);
        $this->service->submitSeries($token, $started->id, $this->seriesRequest(['chique', 'hit', 'carreau']));

        $current = $this->service->current($token);
        self::assertNotNull($current);
        self::assertSame($started->id, $current->id);
        self::assertSame(7, $current->currentDistance);
        self::assertSame(1, $current->seriesCount);
    }

    public function testStartFailsWithoutLinkedPlayer(): void
    {
        $token = $this->createUserWithoutLinkedPlayer();
        $this->expectException(NoLinkedPlayerException::class);
        $this->service->start($token);
    }

    public function testRevertLastSeriesRestoresDistanceAndThrows(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $this->service->submitSeries($token, $started->id, $this->seriesRequest(['chique', 'hit', 'carreau']));
        $reverted = $this->service->revertLastSeries($token, $started->id);

        self::assertSame(0, $reverted->session->seriesCount);
        self::assertSame(6, $reverted->session->currentDistance);
        self::assertCount(3, $reverted->restoredThrows);
        self::assertSame('carreau', $reverted->restoredThrows[2]->result);
    }

    public function testRevertAfterVictoryReopensSession(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);
        $sessionId = $started->id;

        for ($i = 0; $i < 4; ++$i) {
            $this->service->submitSeries($token, $sessionId, $this->seriesRequest(['chique', 'hit', 'carreau']));
        }

        $summary = $this->service->getSummary($token, $sessionId);
        self::assertTrue($summary->won);

        $reverted = $this->service->revertLastSeries($token, $sessionId);
        self::assertFalse($reverted->session->won);
        self::assertNull($reverted->session->finishedAt);
        self::assertSame(9, $reverted->session->currentDistance);
        self::assertSame(3, $reverted->session->seriesCount);
    }

    /**
     * @param array<int|string, string> $resultsByPosition
     */
    private function seriesRequest(array $resultsByPosition): SubmitJumpShotSeriesRequest
    {
        $req = new SubmitJumpShotSeriesRequest();
        if (array_is_list($resultsByPosition)) {
            foreach ($resultsByPosition as $index => $result) {
                $throw = new JumpShotThrowInputDto();
                $throw->position = $index + 1;
                $throw->result = $result;
                $req->throws[] = $throw;
            }

            return $req;
        }

        foreach ($resultsByPosition as $key => $result) {
            $throw = new JumpShotThrowInputDto();
            $throw->position = (int) $key;
            $throw->result = $result;
            $req->throws[] = $throw;
        }

        return $req;
    }

    /**
     * @return array{0:string,1:Player}
     */
    private function createUserWithLinkedPlayer(): array
    {
        $email = sprintf('jumpshot-%s@example.test', bin2hex(random_bytes(6)));
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
        $email = sprintf('jumpshot-%s@example.test', bin2hex(random_bytes(6)));
        $user = new User($email);
        $this->em->persist($user);
        $this->em->flush();

        return $this->jwtEncoder->encode(['username' => $email, 'sub' => (string) $user->getId()]);
    }
}
