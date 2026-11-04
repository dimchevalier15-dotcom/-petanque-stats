<?php

declare(strict_types=1);

namespace App\Tests\Service\Rings;

use App\Entity\Player;
use App\Entity\User;
use App\Service\Rings\RingsSessionService;
use App\Service\Rings\RingsStatsService;
use App\Tests\Support\KernelDatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class RingsStatsServiceTest extends KernelDatabaseTestCase
{
    private EntityManagerInterface $em;
    private RingsSessionService $sessions;
    private RingsStatsService $stats;
    private JWTEncoderInterface $jwtEncoder;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->sessions = $container->get(RingsSessionService::class);
        $this->stats = $container->get(RingsStatsService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
    }

    public function testEmptyPlayerHasNoSessionsStatus(): void
    {
        $token = $this->createUserWithLinkedPlayer();
        $res = $this->stats->stats($token);

        self::assertSame('no_sessions', $res->status);
        self::assertSame(0, $res->summary->sessionsCount);
        self::assertCount(10, $res->scoreByDecile);
    }

    public function testAggregatesCompletedSessions(): void
    {
        $token = $this->createUserWithLinkedPlayer();

        $session1 = $this->sessions->start($token);
        $this->sessions->complete($token, $session1->id, $this->buildRequest([
            ['far', true],
            ...array_fill(0, 14, ['near', false]),
        ]));

        $session2 = $this->sessions->start($token);
        $this->sessions->complete($token, $session2->id, $this->buildRequest(array_fill(0, 15, ['near', true])));

        $res = $this->stats->stats($token);

        self::assertSame('ok', $res->status);
        self::assertSame(2, $res->summary->sessionsCount);
        self::assertSame(30, $res->summary->totalThrows);
        self::assertSame(15, $res->summary->bestSessionScore);
        self::assertSame(10.0, $res->summary->averageSessionScore);
        self::assertSame(3, \count($res->byTarget));
        $far = $this->findTarget($res->byTarget, 'far');
        $near = $this->findTarget($res->byTarget, 'near');
        self::assertSame(1, $far->successCount);
        self::assertSame(1, $far->attemptCount);
        self::assertSame(29, $near->attemptCount);
        self::assertSame(15, $near->successCount);
        self::assertSame(2, array_sum(array_map(static fn ($d) => $d->sessionCount, $res->scoreByDecile)));
        self::assertSame(75, $res->summary->maxSessionScore);
        self::assertSame(0, $res->summary->carreauCount);
    }

    public function testShootingStatsCountCarreauxAndIgnorePointSessions(): void
    {
        $token = $this->createUserWithLinkedPlayer();

        $point = $this->sessions->start($token);
        $this->sessions->complete($token, $point->id, $this->buildRequest(array_fill(0, 15, ['far', true])));

        $startShooting = new \App\Dto\Request\StartRingsSessionRequest();
        $startShooting->mode = 'shooting';
        $shooting = $this->sessions->start($token, $startShooting);
        $this->sessions->complete($token, $shooting->id, $this->buildRequest([
            ...array_fill(0, 3, ['far', true, true]),
            ['far', true],
            ['middle', true, true],
            ...array_fill(0, 10, ['near', false]),
        ]));

        $res = $this->stats->stats($token, mode: \App\Enum\RingsMode::SHOOTING);

        self::assertSame(1, $res->summary->sessionsCount);
        self::assertSame(3 * 10 + 5 + 6, $res->summary->bestSessionScore);
        self::assertSame(150, $res->summary->maxSessionScore);
        self::assertSame(4, $res->summary->carreauCount);
        self::assertSame(26.7, $res->summary->carreauRate);
        $far = $this->findTarget($res->byTarget, 'far');
        self::assertSame(4, $far->successCount);
        self::assertSame(3, $far->carreauCount);
        self::assertSame(150, $res->scoreByDecile[9]->maxScore);
        self::assertSame(1, $res->scoreByDecile[2]->sessionCount);
    }

    private function createUserWithLinkedPlayer(): string
    {
        $email = sprintf('rings-stats-%s@example.test', bin2hex(random_bytes(6)));
        $user = new User($email);
        $this->em->persist($user);
        $this->em->flush();

        $player = new Player('Jean', 'Bernard', 'Jeannot');
        $player->setUser($user);
        $this->em->persist($player);
        $this->em->flush();

        return $this->jwtEncoder->encode(['username' => $email, 'sub' => (string) $user->getId()]);
    }

    /**
     * @param list<array{0:string,1:bool,2?:bool}> $throws
     */
    private function buildRequest(array $throws): \App\Dto\Request\CompleteRingsSessionRequest
    {
        $req = new \App\Dto\Request\CompleteRingsSessionRequest();
        foreach ($throws as $throw) {
            $input = new \App\Dto\Request\RingsThrowInputDto();
            $input->target = $throw[0];
            $input->success = $throw[1];
            $input->carreau = $throw[2] ?? false;
            $req->throws[] = $input;
        }

        return $req;
    }

    /**
     * @param list<\App\Dto\Response\RingsStatsTargetResponse> $rows
     */
    private function findTarget(array $rows, string $target): \App\Dto\Response\RingsStatsTargetResponse
    {
        foreach ($rows as $row) {
            if ($row->target === $target) {
                return $row;
            }
        }

        self::fail('Target not found: '.$target);
    }
}
