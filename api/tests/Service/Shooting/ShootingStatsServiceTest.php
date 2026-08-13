<?php

declare(strict_types=1);

namespace App\Tests\Service\Shooting;

use App\Dto\Request\CompleteShootingSessionRequest;
use App\Dto\Request\ShootingShotInputDto;
use App\Dto\Request\UpdateShootingSessionContextRequest;
use App\Entity\Player;
use App\Entity\User;
use App\Enum\ShootingContextNature;
use App\Service\Shooting\NoLinkedPlayerException;
use App\Service\Shooting\ShootingSessionService;
use App\Service\Shooting\ShootingStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ShootingStatsServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ShootingSessionService $sessions;
    private ShootingStatsService $stats;
    private JWTEncoderInterface $jwtEncoder;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->sessions = $container->get(ShootingSessionService::class);
        $this->stats = $container->get(ShootingStatsService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
    }

    public function testStatsFailsWhenTheAccountHasNoLinkedPlayer(): void
    {
        $token = $this->createUserWithoutLinkedPlayer();

        $this->expectException(NoLinkedPlayerException::class);
        $this->stats->stats($token);
    }

    public function testStatsReturnsNoSessionsWhenThePlayerHasNeverFinishedASession(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $res = $this->stats->stats($token);

        self::assertSame('no_sessions', $res->status);
        self::assertSame(0, $res->summary->sessionsCount);
    }

    public function testStatsAggregatesWorkshopDistanceAndResultBreakdowns(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();
        $started = $this->sessions->start($token);
        $this->sessions->complete($token, $started->id, $this->buildRequest(fn (int $w) => $w === 5 ? 'successful' : 'carreau'));

        $res = $this->stats->stats($token);

        self::assertSame('ok', $res->status);
        self::assertSame(1, $res->summary->sessionsCount);
        self::assertSame(20, $res->summary->totalShots);
        self::assertSame(100, $res->summary->bestSessionScore);
        self::assertSame(100.0, $res->summary->averageSessionScore);
        self::assertCount(5, $res->byWorkshop);
        self::assertCount(4, $res->byDistance);
        self::assertCount(2, $res->byResult);
        self::assertCount(20, $res->heatmap);
        self::assertCount(1, $res->evolution);
    }

    public function testStatsCanBeFilteredByContextNature(): void
    {
        [$token] = $this->createUserWithLinkedPlayer();

        $training = $this->sessions->start($token);
        $this->sessions->complete($token, $training->id, $this->buildRequest(fn () => 'touched'));
        $trainingReq = new UpdateShootingSessionContextRequest();
        $trainingReq->contextNature = ShootingContextNature::TRAINING->value;
        $this->sessions->updateContext($token, $training->id, $trainingReq);

        $competition = $this->sessions->start($token);
        $this->sessions->complete($token, $competition->id, $this->buildRequest(fn (int $w) => $w === 5 ? 'successful' : 'carreau'));
        $competitionReq = new UpdateShootingSessionContextRequest();
        $competitionReq->contextNature = ShootingContextNature::COMPETITION->value;
        $this->sessions->updateContext($token, $competition->id, $competitionReq);

        $all = $this->stats->stats($token);
        $trainingOnly = $this->stats->stats($token, ShootingContextNature::TRAINING);
        $competitionOnly = $this->stats->stats($token, ShootingContextNature::COMPETITION);

        self::assertSame(2, $all->summary->sessionsCount);
        self::assertSame(1, $trainingOnly->summary->sessionsCount);
        self::assertSame(1, $competitionOnly->summary->sessionsCount);
        self::assertSame(100, $competitionOnly->summary->bestSessionScore);
    }

    /**
     * @return array{0:string,1:Player}
     */
    private function createUserWithLinkedPlayer(): array
    {
        $email = sprintf('shooting-stats-%s@example.test', bin2hex(random_bytes(6)));
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
        $email = sprintf('shooting-stats-%s@example.test', bin2hex(random_bytes(6)));
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
