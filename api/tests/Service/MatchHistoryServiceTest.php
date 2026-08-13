<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Request\CreateMatchRequest;
use App\Entity\User;
use App\Service\MatchHistoryService;
use App\Service\MatchRecordingService;
use App\Service\MatchService;
use App\Tests\Support\MatchTestHelpers;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MatchHistoryServiceTest extends KernelTestCase
{
    use MatchTestHelpers;

    private MatchHistoryService $history;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->matchService = $container->get(MatchService::class);
        $this->recording = $container->get(MatchRecordingService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
        $this->history = $container->get(MatchHistoryService::class);
    }

    public function testUncompletedMatchDoesNotAppearInHistory(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $suffix = bin2hex(random_bytes(4));
        $owner = new User('owner'.$suffix.'@test.local');
        $owner->setPassword('hash');
        $this->em->persist($owner);

        $createReq = new CreateMatchRequest();
        $createReq->type = 'tete_a_tete';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = [$playerId];
        $createReq->teamB = [$opponentId];
        $createReq->trackedPlayers = [$playerId, $opponentId];
        $this->matchService->create($createReq, $owner);

        $res = $this->history->historyForToken($token);

        self::assertSame(0, $res->total);
        self::assertSame([], $res->items);
    }

    public function testCompletedMatchAppearsInHistoryWithScore(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();
        [$matchId] = $this->createHeadToHeadForPlayers($playerId, $opponentId);

        $this->completeHeadToHead($matchId, $playerId, $opponentId, 4);

        $res = $this->history->historyForToken($token);

        self::assertSame(1, $res->total);
        self::assertCount(1, $res->items);
        self::assertSame($matchId, $res->items[0]->id);
        self::assertSame(4, $res->items[0]->scoreA);
        self::assertSame(0, $res->items[0]->scoreB);
        self::assertTrue($res->items[0]->victory);
    }

    public function testHistoryReturnsEmptyWhenAccountHasNoLinkedPlayer(): void
    {
        $email = sprintf('history-no-player-%s@test.local', bin2hex(random_bytes(4)));
        $user = new User($email);
        $user->setPassword('hash');
        $this->em->persist($user);
        $this->em->flush();

        $token = $this->jwtEncoder->encode(['username' => $email, 'sub' => (string) $user->getId()]);

        $res = $this->history->historyForToken($token);

        self::assertSame(0, $res->total);
        self::assertSame([], $res->items);
    }

    /**
     * @return array{0:int}
     */
    private function createHeadToHeadForPlayers(int $playerAId, int $playerBId): array
    {
        $suffix = bin2hex(random_bytes(4));
        $owner = new User('owner'.$suffix.'@test.local');
        $owner->setPassword('hash');
        $this->em->persist($owner);

        $createReq = new CreateMatchRequest();
        $createReq->type = 'tete_a_tete';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = [$playerAId];
        $createReq->teamB = [$playerBId];
        $createReq->trackedPlayers = [$playerAId, $playerBId];

        $matchId = $this->matchService->create($createReq, $owner)->id;

        return [$matchId];
    }
}
