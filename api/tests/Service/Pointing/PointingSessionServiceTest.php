<?php

declare(strict_types=1);

namespace App\Tests\Service\Pointing;

use App\Dto\Request\CompletePointingSessionRequest;
use App\Dto\Request\PointingShotInputDto;
use App\Entity\Player;
use App\Entity\User;
use App\Service\Pointing\InvalidPointingSessionStructureException;
use App\Service\Pointing\PointingSessionAccessDeniedException;
use App\Service\Pointing\PointingSessionAlreadyFinishedException;
use App\Service\Pointing\PointingSessionNotFoundException;
use App\Service\Pointing\PointingSessionService;
use App\Service\Pointing\NoLinkedPlayerException;
use App\Tests\Support\KernelDatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class PointingSessionServiceTest extends KernelDatabaseTestCase
{
    private EntityManagerInterface $em;
    private PointingSessionService $service;
    private JWTEncoderInterface $jwtEncoder;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(PointingSessionService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
    }

    public function testStartCreatesASessionAndItBecomesCurrent(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        self::assertNull($this->service->current($token));

        $started = $this->service->start($token);

        $current = $this->service->current($token);
        self::assertNotNull($current);
        self::assertSame($started->id, $current->id);
    }

    public function testCompletingAFullSessionComputesThreeHundredWhenAllBestTiers(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $summary = $this->service->complete($token, $started->id, $this->buildRequest(fn () => 'p15'));

        self::assertSame(300, $summary->totalScore);
        self::assertNotNull($summary->finishedAt);
        self::assertCount(5, $summary->workshops);
    }

    public function testCompletingWithMissingShotFails(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $req = $this->buildRequest(fn () => 'p3');
        array_pop($req->shots);

        $this->expectException(InvalidPointingSessionStructureException::class);
        $this->service->complete($token, $started->id, $req);
    }

    public function testAnotherPlayerCannotReadSummary(): void
    {
        [$ownerToken] = $this->createUserWithLinkedPlayer();
        [$otherToken] = $this->createUserWithLinkedPlayer();

        $started = $this->service->start($ownerToken);

        $this->expectException(PointingSessionAccessDeniedException::class);
        $this->service->getSummary($otherToken, $started->id);
    }

    public function testAbandonRemovesInProgressSession(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $this->service->abandon($token, $started->id);

        self::assertNull($this->service->current($token));
        $this->expectException(PointingSessionNotFoundException::class);
        $this->service->getSummary($token, $started->id);
    }

    public function testStartFailsWithoutLinkedPlayer(): void
    {
        $token = $this->createUserWithoutLinkedPlayer();

        $this->expectException(NoLinkedPlayerException::class);
        $this->service->start($token);
    }

    public function testCompletingTwiceFails(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);
        $this->service->complete($token, $started->id, $this->buildRequest(fn () => 'missed'));

        $this->expectException(PointingSessionAlreadyFinishedException::class);
        $this->service->complete($token, $started->id, $this->buildRequest(fn () => 'missed'));
    }

    /**
     * @return array{0:string,1:Player}
     */
    private function createUserWithLinkedPlayer(): array
    {
        $email = sprintf('pointing-%s@example.test', bin2hex(random_bytes(6)));
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
        $email = sprintf('pointing-%s@example.test', bin2hex(random_bytes(6)));
        $user = new User($email);
        $this->em->persist($user);
        $this->em->flush();

        return $this->jwtEncoder->encode(['username' => $email, 'sub' => (string) $user->getId()]);
    }

    /**
     * @param callable(int): string $resultForWorkshop
     */
    private function buildRequest(callable $resultForWorkshop): CompletePointingSessionRequest
    {
        $req = new CompletePointingSessionRequest();
        foreach ([1, 2, 3, 4, 5] as $workshop) {
            foreach ([6, 7, 8, 9] as $distance) {
                $shot = new PointingShotInputDto();
                $shot->workshop = $workshop;
                $shot->distance = $distance;
                $shot->result = $resultForWorkshop($workshop);
                $req->shots[] = $shot;
            }
        }

        return $req;
    }
}
