<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Request\CreateMatchRequest;
use App\Dto\Request\UpdateMatchContextRequest;
use App\Entity\Competition;
use App\Entity\User;
use App\Service\MatchContextService;
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
    private MatchContextService $context;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->matchService = $container->get(MatchService::class);
        $this->recording = $container->get(MatchRecordingService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
        $this->history = $container->get(MatchHistoryService::class);
        $this->context = $container->get(MatchContextService::class);
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

    public function testHistoryIncludesMatchContextWhenPresent(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();
        [$matchId] = $this->createHeadToHeadForPlayers($playerId, $opponentId);

        $this->completeHeadToHead($matchId, $playerId, $opponentId, 4);

        $competition = new Competition('Open test', new \DateTimeImmutable('2026-05-10'), 'France', 'National');
        $this->em->persist($competition);
        $this->em->flush();

        $req = new UpdateMatchContextRequest();
        $req->nature = 'competition';
        $req->competitionId = (int) $competition->getId();
        $req->competitionStage = 'final';
        $this->context->updateContext($matchId, $req);

        $res = $this->history->historyForToken($token);

        self::assertSame('competition', $res->items[0]->nature);
        self::assertSame('Open test - 2026', $res->items[0]->competitionLabel);
        self::assertSame('final', $res->items[0]->competitionStage);
    }

    public function testHistoryCanBeLoadedForImpersonatedPlayer(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $suffix = bin2hex(random_bytes(4));
        $coach = new User('coach-history-'.$suffix.'@test.local');
        $coach->setPassword('hash');
        $this->em->persist($coach);
        $this->em->flush();
        $coachToken = $this->jwtEncoder->encode(['username' => $coach->getEmail(), 'sub' => (string) $coach->getId()]);

        [$matchId] = $this->createHeadToHeadForPlayers($playerId, $opponentId);
        $this->completeHeadToHead($matchId, $playerId, $opponentId, 3);

        $res = $this->history->historyForToken($coachToken, 1, 20, $playerId);

        self::assertSame(1, $res->total);
        self::assertSame($matchId, $res->items[0]->id);
        self::assertTrue($res->items[0]->victory);
    }

    public function testHistoryReturnsEmptyWhenAccountHasNoLinkedPlayerAndNoCreatedMatches(): void
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

    public function testCreatedMatchAppearsWhenCreatorDidNotParticipate(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $suffix = bin2hex(random_bytes(4));
        $coach = new User('coach'.$suffix.'@test.local');
        $coach->setPassword('hash');
        $this->em->persist($coach);
        $this->em->flush();

        $coachToken = $this->jwtEncoder->encode(['username' => $coach->getEmail(), 'sub' => (string) $coach->getId()]);

        $createReq = new CreateMatchRequest();
        $createReq->type = 'tete_a_tete';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = [$playerId];
        $createReq->teamB = [$opponentId];
        $createReq->trackedPlayers = [$playerId, $opponentId];
        $matchId = $this->matchService->create($createReq, $coach)->id;

        $this->completeHeadToHead($matchId, $playerId, $opponentId, 3);

        $res = $this->history->historyForToken($coachToken);

        self::assertSame(1, $res->total);
        self::assertCount(1, $res->items);
        self::assertSame($matchId, $res->items[0]->id);
        self::assertSame(3, $res->items[0]->scoreA);
        self::assertNull($res->items[0]->victory);
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
