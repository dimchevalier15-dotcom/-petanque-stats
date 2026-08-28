<?php

declare(strict_types=1);

namespace App\Tests\Service\Shooting;

use App\Dto\Request\CompleteShootingSessionRequest;
use App\Dto\Request\ShootingShotInputDto;
use App\Dto\Request\UpdateShootingSessionContextRequest;
use App\Entity\Player;
use App\Entity\User;
use App\Service\Shooting\InvalidShootingSessionStructureException;
use App\Service\Shooting\NoLinkedPlayerException;
use App\Service\Shooting\ShootingSessionAccessDeniedException;
use App\Service\Shooting\ShootingSessionAlreadyFinishedException;
use App\Service\Shooting\ShootingSessionNotFoundException;
use App\Service\Shooting\ShootingSessionService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Functional tests: ShootingSessionService's collaborators (CurrentUserService,
 * the repositories) are declared final, like the rest of the codebase's
 * services, so they cannot be mocked. We exercise the real service against
 * the test database instead, which also verifies the authorization rules
 * end-to-end.
 */
final class ShootingSessionServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ShootingSessionService $service;
    private JWTEncoderInterface $jwtEncoder;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(ShootingSessionService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
    }

    public function testStartCreatesASessionForTheLinkedPlayerAndItBecomesTheCurrentOne(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        self::assertNull($this->service->current($token));

        $started = $this->service->start($token);

        $current = $this->service->current($token);
        self::assertNotNull($current);
        self::assertSame($started->id, $current->id);
    }

    public function testStartFailsWhenTheAccountHasNoLinkedPlayer(): void
    {
        $token = $this->createUserWithoutLinkedPlayer();

        $this->expectException(NoLinkedPlayerException::class);
        $this->service->start($token);
    }

    public function testASummaryCannotBeReadByAnotherPlayer(): void
    {
        [$ownerToken] = $this->createUserWithLinkedPlayer();
        [$otherToken] = $this->createUserWithLinkedPlayer();

        $started = $this->service->start($ownerToken);

        $this->expectException(ShootingSessionAccessDeniedException::class);
        $this->service->getSummary($otherToken, $started->id);
    }

    public function testReadingAnUnknownSessionFails(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $this->expectException(ShootingSessionNotFoundException::class);
        $this->service->getSummary($token, 999999999);
    }

    public function testCompletingAFullSessionComputesTheOfficialScoreIncludingTheJackWorkshopRule(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        // Workshops 1-4: carreau (5 pts) x 4 distances x 4 workshops = 80
        // Workshop 5 (jack): successful (5 pts, no carreau) x 4 distances = 20
        $req = $this->buildRequest(function (int $workshop): string {
            return $workshop === 5 ? 'successful' : 'carreau';
        });

        $summary = $this->service->complete($token, $started->id, $req);

        self::assertSame(100, $summary->totalScore);
        self::assertNotNull($summary->finishedAt);
        self::assertCount(5, $summary->workshops);
    }

    public function testCompletingAnAlreadyFinishedSessionFails(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);
        $this->service->complete($token, $started->id, $this->buildRequest(fn () => 'touched'));

        $this->expectException(ShootingSessionAlreadyFinishedException::class);
        $this->service->complete($token, $started->id, $this->buildRequest(fn () => 'touched'));
    }

    public function testCompletingASessionWithAMissingShotFails(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $req = $this->buildRequest(fn () => 'touched');
        array_pop($req->shots);

        $this->expectException(InvalidShootingSessionStructureException::class);
        $this->service->complete($token, $started->id, $req);
    }

    public function testCompletingASessionWithCarreauOnTheJackWorkshopFails(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $req = $this->buildRequest(function (int $workshop): string {
            return $workshop === 5 ? 'carreau' : 'touched';
        });

        $this->expectException(InvalidShootingSessionStructureException::class);
        $this->service->complete($token, $started->id, $req);
    }

    public function testAbandoningAnUnfinishedSessionRemovesItAndItIsNoLongerCurrent(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $this->service->abandon($token, $started->id);

        self::assertNull($this->service->current($token));
        $this->expectException(ShootingSessionNotFoundException::class);
        $this->service->getSummary($token, $started->id);
    }

    public function testContextCanBeAddedOnceASessionIsFinished(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);
        $this->service->complete($token, $started->id, $this->buildRequest(fn () => 'touched'));

        $req = new UpdateShootingSessionContextRequest();
        $req->contextNature = 'training';
        $req->title = 'Entraînement du soir';
        $req->description = 'Bon ressenti.';
        $req->playedAt = '2024-09-28';

        $summary = $this->service->updateContext($token, $started->id, $req);

        self::assertSame('training', $summary->contextNature);
        self::assertSame('Entraînement du soir', $summary->title);
        self::assertSame('Bon ressenti.', $summary->description);
        self::assertStringStartsWith('2024-09-28', $summary->playedAt);
    }

    public function testContextCannotBeAddedByAnotherPlayer(): void
    {
        [$ownerToken] = $this->createUserWithLinkedPlayer();
        [$otherToken] = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($ownerToken);

        $req = new UpdateShootingSessionContextRequest();
        $req->title = 'Tentative';

        $this->expectException(ShootingSessionAccessDeniedException::class);
        $this->service->updateContext($otherToken, $started->id, $req);
    }

    public function testAPlayerCanHaveSeveralSessionsInTheirHistory(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $first = $this->service->start($token);
        $this->service->complete($token, $first->id, $this->buildRequest(fn () => 'missed'));

        $second = $this->service->start($token);
        $this->service->complete($token, $second->id, $this->buildRequest(fn () => 'touched'));

        $history = $this->service->history($token, 1, 20);

        self::assertSame(2, $history->total);
    }

    /**
     * @return array{0:string,1:Player}
     */
    private function createUserWithLinkedPlayer(): array
    {
        $email = sprintf('shooting-%s@example.test', bin2hex(random_bytes(6)));
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
        $email = sprintf('shooting-%s@example.test', bin2hex(random_bytes(6)));
        $user = new User($email);
        $this->em->persist($user);
        $this->em->flush();

        return $this->jwtEncoder->encode(['username' => $email, 'sub' => (string) $user->getId()]);
    }

    /**
     * @param callable(int): string $resultForWorkshop
     */
    private function buildRequest(callable $resultForWorkshop): CompleteShootingSessionRequest
    {
        $req = new CompleteShootingSessionRequest();
        foreach ([1, 2, 3, 4, 5] as $workshop) {
            foreach ([6, 7, 8, 9] as $distance) {
                $shot = new ShootingShotInputDto();
                $shot->workshop = $workshop;
                $shot->distance = $distance;
                $shot->result = $resultForWorkshop($workshop);
                $req->shots[] = $shot;
            }
        }

        return $req;
    }
}
