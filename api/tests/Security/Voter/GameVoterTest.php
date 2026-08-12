<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter;

use App\Entity\Game;
use App\Entity\Player;
use App\Entity\User;
use App\Enum\GameType;
use App\Security\Voter\GameVoter;
use App\Service\MatchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class GameVoterTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private MatchService $matchService;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenStorageInterface $tokenStorage;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->matchService = $container->get(MatchService::class);
        $this->authorizationChecker = $container->get(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $container->get(TokenStorageInterface::class);
    }

    public function testOwnerCanViewAndEditMatch(): void
    {
        [$game, $owner] = $this->createMatchWithOwner();

        $this->authenticateAs($owner);

        self::assertTrue($this->authorizationChecker->isGranted(GameVoter::VIEW, $game));
        self::assertTrue($this->authorizationChecker->isGranted(GameVoter::EDIT, $game));
    }

    public function testParticipantCanViewAndEditMatch(): void
    {
        [$game, , $participantUser] = $this->createMatchWithParticipantUser();

        $this->authenticateAs($participantUser);

        self::assertTrue($this->authorizationChecker->isGranted(GameVoter::VIEW, $game));
        self::assertTrue($this->authorizationChecker->isGranted(GameVoter::EDIT, $game));
    }

    public function testUnrelatedUserIsDenied(): void
    {
        [$game] = $this->createMatchWithOwner();
        $stranger = $this->createUser('stranger');

        $this->authenticateAs($stranger);

        self::assertFalse($this->authorizationChecker->isGranted(GameVoter::VIEW, $game));
        self::assertFalse($this->authorizationChecker->isGranted(GameVoter::EDIT, $game));
    }

    public function testLegacyMatchWithoutOwnerAllowsParticipantOnly(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $player = new Player('Legacy', 'Player'.$suffix, 'Leg'.$suffix);
        $participantUser = $this->createUser('participant'.$suffix);
        $player->setUser($participantUser);
        $opponent = new Player('Other', 'Player'.$suffix, 'Oth'.$suffix);
        $this->em->persist($player);
        $this->em->persist($opponent);
        $this->em->flush();

        $game = new Game(GameType::TETE_A_TETE);
        $this->em->persist($game);
        $this->em->persist(new \App\Entity\GameParticipant($game, $player, 'A', 1, 'point'));
        $this->em->persist(new \App\Entity\GameParticipant($game, $opponent, 'B', 1, 'point'));
        $this->em->flush();

        $this->authenticateAs($participantUser);

        self::assertTrue($this->authorizationChecker->isGranted(GameVoter::VIEW, $game));
        self::assertTrue($this->authorizationChecker->isGranted(GameVoter::EDIT, $game));
    }

    /**
     * @return array{0: Game, 1: User}
     */
    private function createMatchWithOwner(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $owner = $this->createUser('owner'.$suffix);
        $playerA = new Player('Alice', 'Owner'.$suffix, 'Ali'.$suffix);
        $playerB = new Player('Bob', 'Other'.$suffix, 'Bob'.$suffix);
        $this->em->persist($playerA);
        $this->em->persist($playerB);
        $this->em->flush();

        $createReq = new \App\Dto\Request\CreateMatchRequest();
        $createReq->type = 'tete_a_tete';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = [(int) $playerA->getId()];
        $createReq->teamB = [(int) $playerB->getId()];
        $createReq->trackedPlayers = [(int) $playerA->getId(), (int) $playerB->getId()];

        $created = $this->matchService->create($createReq, $owner);
        $game = $this->em->getRepository(Game::class)->find($created->id);
        self::assertInstanceOf(Game::class, $game);

        return [$game, $owner];
    }

    /**
     * @return array{0: Game, 1: User, 2: User}
     */
    private function createMatchWithParticipantUser(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $owner = $this->createUser('owner'.$suffix);
        $participantUser = $this->createUser('participant'.$suffix);
        $participant = new Player('Carol', 'Play'.$suffix, 'Car'.$suffix);
        $participant->setUser($participantUser);
        $opponent = new Player('Dave', 'Other'.$suffix, 'Dav'.$suffix);
        $this->em->persist($participant);
        $this->em->persist($opponent);
        $this->em->flush();

        $createReq = new \App\Dto\Request\CreateMatchRequest();
        $createReq->type = 'tete_a_tete';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = [(int) $participant->getId()];
        $createReq->teamB = [(int) $opponent->getId()];
        $createReq->trackedPlayers = [(int) $participant->getId(), (int) $opponent->getId()];

        $created = $this->matchService->create($createReq, $owner);
        $game = $this->em->getRepository(Game::class)->find($created->id);
        self::assertInstanceOf(Game::class, $game);

        return [$game, $owner, $participantUser];
    }

    private function createUser(string $prefix): User
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User($prefix.$suffix.'@test.local');
        $user->setPassword('hash');
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function authenticateAs(User $user): void
    {
        $this->tokenStorage->setToken(new UsernamePasswordToken($user, 'api', $user->getRoles()));
    }
}
