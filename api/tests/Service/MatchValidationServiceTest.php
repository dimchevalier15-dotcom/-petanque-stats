<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Request\CreateMatchRequest;
use App\Entity\Player;
use App\Entity\User;
use App\Repository\GameParticipantRepository;
use App\Service\MatchHistoryService;
use App\Service\MatchRecordingService;
use App\Service\MatchService;
use App\Service\MatchValidationService;
use App\Tests\Support\KernelDatabaseTestCase;
use App\Tests\Support\MatchTestHelpers;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class MatchValidationServiceTest extends KernelDatabaseTestCase
{
    use MatchTestHelpers;

    private MatchValidationService $validation;
    private MatchHistoryService $history;
    private GameParticipantRepository $participants;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->matchService = $container->get(MatchService::class);
        $this->recording = $container->get(MatchRecordingService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
        $this->validation = $container->get(MatchValidationService::class);
        $this->history = $container->get(MatchHistoryService::class);
        $this->participants = $container->get(GameParticipantRepository::class);
    }

    public function testCreatorIsValidatedWhenRequiresValidationEnabled(): void
    {
        [$creatorToken, $creatorPlayer, $opponentId] = $this->createLinkedPlayerWithValidationRequired();
        $creatorId = (int) $creatorPlayer->getId();
        $matchId = $this->createMatchForPlayers($creatorId, $opponentId, $creatorPlayer->getUser());

        $participation = $this->participants->findByGameAndPlayer(
            $this->em->getRepository(\App\Entity\Game::class)->find($matchId),
            $creatorId,
        );
        self::assertTrue($participation?->getHasValidatedMatch());
    }

    public function testAddedPlayerIsPendingWhenRequiresValidationEnabled(): void
    {
        [$creatorToken, $creatorPlayer, $opponentId] = $this->createLinkedPlayerWithValidationRequired();
        $addedUser = $this->createUserWithValidationRequired('added');
        $addedPlayer = $this->createLinkedPlayerForUser($addedUser, 'Marc', 'Martin');
        $matchId = $this->createMatchForPlayers((int) $creatorPlayer->getId(), (int) $addedPlayer->getId(), $creatorPlayer->getUser());
        $this->completeHeadToHead($matchId, (int) $creatorPlayer->getId(), (int) $addedPlayer->getId(), 5);

        $participation = $this->participants->findByGameAndPlayer(
            $this->em->getRepository(\App\Entity\Game::class)->find($matchId),
            (int) $addedPlayer->getId(),
        );
        self::assertNull($participation?->getHasValidatedMatch());

        $addedToken = $this->jwtEncoder->encode(['username' => $addedUser->getEmail(), 'sub' => (string) $addedUser->getId()]);
        $pending = $this->validation->pendingForToken($addedToken);
        self::assertSame(1, $pending->total);

        $history = $this->history->historyForToken($addedToken);
        self::assertSame(0, $history->total);
    }

    public function testRefusedMatchExcludedFromHistory(): void
    {
        [$creatorToken, $creatorPlayer, $opponentId] = $this->createLinkedPlayerWithValidationRequired();
        $addedUser = $this->createUserWithValidationRequired('refused');
        $addedPlayer = $this->createLinkedPlayerForUser($addedUser, 'Paul', 'Durand');
        $matchId = $this->createMatchForPlayers((int) $creatorPlayer->getId(), (int) $addedPlayer->getId(), $creatorPlayer->getUser());
        $this->completeHeadToHead($matchId, (int) $creatorPlayer->getId(), (int) $addedPlayer->getId(), 5);

        $addedToken = $this->jwtEncoder->encode(['username' => $addedUser->getEmail(), 'sub' => (string) $addedUser->getId()]);
        $pending = $this->validation->pendingForToken($addedToken);
        $matchPlayerId = $pending->items[0]->matchPlayerId;

        $this->validation->updateValidation($addedToken, $matchPlayerId, false);

        $history = $this->history->historyForToken($addedToken);
        self::assertSame(0, $history->total);
        self::assertSame(0, $this->validation->countPendingForToken($addedToken)->count);
    }

    public function testValidatedMatchAppearsInHistory(): void
    {
        [$creatorToken, $creatorPlayer, $opponentId] = $this->createLinkedPlayerWithValidationRequired();
        $addedUser = $this->createUserWithValidationRequired('accepted');
        $addedPlayer = $this->createLinkedPlayerForUser($addedUser, 'Luc', 'Bernard');
        $matchId = $this->createMatchForPlayers((int) $creatorPlayer->getId(), (int) $addedPlayer->getId(), $creatorPlayer->getUser());
        $this->completeHeadToHead($matchId, (int) $creatorPlayer->getId(), (int) $addedPlayer->getId(), 5);

        $addedToken = $this->jwtEncoder->encode(['username' => $addedUser->getEmail(), 'sub' => (string) $addedUser->getId()]);
        $pending = $this->validation->pendingForToken($addedToken);
        $this->validation->updateValidation($addedToken, $pending->items[0]->matchPlayerId, true);

        $history = $this->history->historyForToken($addedToken);
        self::assertSame(1, $history->total);
    }

    public function testCannotUpdateAnotherPlayersValidation(): void
    {
        [$creatorToken, $creatorPlayer, $opponentId] = $this->createLinkedPlayerWithValidationRequired();
        $addedUser = $this->createUserWithValidationRequired('other');
        $addedPlayer = $this->createLinkedPlayerForUser($addedUser, 'Tom', 'Petit');
        $matchId = $this->createMatchForPlayers((int) $creatorPlayer->getId(), (int) $addedPlayer->getId(), $creatorPlayer->getUser());
        $this->completeHeadToHead($matchId, (int) $creatorPlayer->getId(), (int) $addedPlayer->getId(), 5);

        $participation = $this->participants->findByGameAndPlayer(
            $this->em->getRepository(\App\Entity\Game::class)->find($matchId),
            (int) $addedPlayer->getId(),
        );

        $this->expectException(\App\Service\MatchValidationOwnershipException::class);
        $this->validation->updateValidation($creatorToken, (int) $participation?->getId(), true);
    }

    /**
     * @return array{0:string,1:Player,2:int}
     */
    private function createLinkedPlayerWithValidationRequired(): array
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $player->getUser()?->setRequiresMatchValidation(true);
        $this->em->flush();

        return [$token, $player, $opponentId];
    }

    private function createUserWithValidationRequired(string $prefix): User
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User($prefix.'-'.$suffix.'@test.local');
        $user->setPassword('hash');
        $user->setRequiresMatchValidation(true);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createLinkedPlayerForUser(User $user, string $firstName, string $lastName): Player
    {
        $suffix = bin2hex(random_bytes(4));
        $player = new Player($firstName, $lastName.$suffix, '');
        $player->setUser($user);
        $this->em->persist($player);
        $this->em->flush();

        return $player;
    }

    private function createMatchForPlayers(int $playerAId, int $playerBId, ?User $creator): int
    {
        if ($creator === null) {
            $creator = new User('fallback-'.bin2hex(random_bytes(4)).'@test.local');
            $creator->setPassword('hash');
            $this->em->persist($creator);
            $this->em->flush();
        }

        $createReq = new CreateMatchRequest();
        $createReq->type = 'tete_a_tete';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = [$playerAId];
        $createReq->teamB = [$playerBId];
        $createReq->trackedPlayers = [$playerAId, $playerBId];

        return $this->matchService->create($createReq, $creator)->id;
    }
}
