<?php

declare(strict_types=1);

namespace App\Tests\Service\Rings;

use App\Dto\Request\CompleteRingsSessionRequest;
use App\Dto\Request\RingsThrowInputDto;
use App\Entity\Player;
use App\Entity\User;
use App\Service\Rings\InvalidRingsSessionStructureException;
use App\Service\Rings\NoLinkedPlayerException;
use App\Service\Rings\RingsSessionAccessDeniedException;
use App\Service\Rings\RingsSessionAlreadyFinishedException;
use App\Service\Rings\RingsSessionNotFoundException;
use App\Service\Rings\RingsSessionService;
use App\Tests\Support\KernelDatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class RingsSessionServiceTest extends KernelDatabaseTestCase
{
    private EntityManagerInterface $em;
    private RingsSessionService $service;
    private JWTEncoderInterface $jwtEncoder;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(RingsSessionService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
    }

    public function testStartCreatesAPointSessionAndItBecomesCurrent(): void
    {
        $token = $this->createUserWithLinkedPlayer();

        self::assertNull($this->service->current($token));

        $started = $this->service->start($token);

        self::assertSame('point', $started->mode);
        self::assertSame($started->id, $this->service->current($token)?->id);
    }

    public function testScoreIsTheSumOfSucceededTargets(): void
    {
        $token = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $throws = [
            ...array_fill(0, 5, ['far', true]),
            ...array_fill(0, 5, ['middle', true]),
            ...array_fill(0, 3, ['near', true]),
            ['far', false],
            ['near', false],
        ];

        $summary = $this->service->complete($token, $started->id, $this->buildRequest($throws));

        self::assertSame(5 * 5 + 5 * 3 + 3 * 1, $summary->totalScore);
        self::assertNotNull($summary->finishedAt);
        self::assertCount(15, $summary->throws);
        self::assertSame(1, $summary->throws[0]->position);
        self::assertSame('far', $summary->throws[13]->target);
        self::assertFalse($summary->throws[13]->success);
        self::assertSame(0, $summary->throws[13]->score);
    }

    public function testAllFarSuccessesGiveTheMaximumScore(): void
    {
        $token = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $summary = $this->service->complete($token, $started->id, $this->buildRequest(array_fill(0, 15, ['far', true])));

        self::assertSame(75, $summary->totalScore);
    }

    public function testCompletingWithMissingThrowFails(): void
    {
        $token = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $this->expectException(InvalidRingsSessionStructureException::class);
        $this->service->complete($token, $started->id, $this->buildRequest(array_fill(0, 14, ['near', true])));
    }

    public function testCompletingTwiceFails(): void
    {
        $token = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);
        $this->service->complete($token, $started->id, $this->buildRequest(array_fill(0, 15, ['near', false])));

        $this->expectException(RingsSessionAlreadyFinishedException::class);
        $this->service->complete($token, $started->id, $this->buildRequest(array_fill(0, 15, ['near', false])));
    }

    public function testAnotherPlayerCannotReadSummary(): void
    {
        $ownerToken = $this->createUserWithLinkedPlayer();
        $otherToken = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($ownerToken);

        $this->expectException(RingsSessionAccessDeniedException::class);
        $this->service->getSummary($otherToken, $started->id);
    }

    public function testAbandonRemovesInProgressSession(): void
    {
        $token = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);

        $this->service->abandon($token, $started->id);

        self::assertNull($this->service->current($token));
        $this->expectException(RingsSessionNotFoundException::class);
        $this->service->getSummary($token, $started->id);
    }

    public function testFinishedSessionAppearsInHistory(): void
    {
        $token = $this->createUserWithLinkedPlayer();
        $started = $this->service->start($token);
        $this->service->complete($token, $started->id, $this->buildRequest(array_fill(0, 15, ['middle', true])));

        $history = $this->service->history($token);

        self::assertSame(1, $history->total);
        self::assertSame(45, $history->items[0]->totalScore);
    }

    public function testStartFailsWithoutLinkedPlayer(): void
    {
        $email = sprintf('rings-%s@example.test', bin2hex(random_bytes(6)));
        $user = new User($email);
        $this->em->persist($user);
        $this->em->flush();
        $token = $this->jwtEncoder->encode(['username' => $email, 'sub' => (string) $user->getId()]);

        $this->expectException(NoLinkedPlayerException::class);
        $this->service->start($token);
    }

    private function createUserWithLinkedPlayer(): string
    {
        $email = sprintf('rings-%s@example.test', bin2hex(random_bytes(6)));
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
     * @param list<array{0:string,1:bool}> $throws
     */
    private function buildRequest(array $throws): CompleteRingsSessionRequest
    {
        $req = new CompleteRingsSessionRequest();
        foreach ($throws as [$target, $success]) {
            $input = new RingsThrowInputDto();
            $input->target = $target;
            $input->success = $success;
            $req->throws[] = $input;
        }

        return $req;
    }
}
