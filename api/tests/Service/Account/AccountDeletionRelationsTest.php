<?php

declare(strict_types=1);

namespace App\Tests\Service\Account;

use App\Dto\Request\CreateMatchRequest;
use App\Entity\Game;
use App\Entity\GameParticipant;
use App\Entity\Player;
use App\Entity\ShootingSession;
use App\Entity\TrainingSession;
use App\Entity\User;
use App\Enum\AuthTokenPurpose;
use App\Enum\TrainingType;
use App\Service\Account\AccountDeletionService;
use App\Service\Auth\AuthTokenIssuer;
use App\Service\MatchService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use App\Tests\Support\KernelDatabaseTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccountDeletionRelationsTest extends KernelDatabaseTestCase
{
    private EntityManagerInterface $em;
    private AccountDeletionService $deletion;
    private JWTEncoderInterface $jwtEncoder;
    private UserPasswordHasherInterface $hasher;
    private MatchService $matches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->deletion = static::getContainer()->get(AccountDeletionService::class);
        $this->jwtEncoder = static::getContainer()->get(JWTEncoderInterface::class);
        $this->hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->matches = static::getContainer()->get(MatchService::class);
    }

    public function testDeletingOneUserDoesNotRemoveAnotherUser(): void
    {
        [$tokenA, $userA, $userB] = $this->persistTwoUsers();
        $userBId = (int) $userB->getId();
        $userAId = (int) $userA->getId();

        $this->deletion->deleteAccount($tokenA);
        $this->em->clear();

        self::assertNull($this->em->find(User::class, $userAId));
        self::assertNotNull($this->em->find(User::class, $userBId));
    }

    public function testAssociatedMatchTrainingAndShootingStayWhenAccountIsDeleted(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $email = 'rel-del-'.$suffix.'@test.local';
        $user = new User($email);
        $user->setPassword($this->hasher->hashPassword($user, 'password123'));
        $this->em->persist($user);

        $player = new Player('Jean', 'Rel'.$suffix, 'JR');
        $player->setUser($user);
        $opponent = new Player('Marie', 'Adv'.$suffix, 'MA');
        $this->em->persist($player);
        $this->em->persist($opponent);
        $this->em->flush();

        $createReq = new CreateMatchRequest();
        $createReq->type = 'tete_a_tete';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = [(int) $player->getId()];
        $createReq->teamB = [(int) $opponent->getId()];
        $created = $this->matches->create($createReq, $user);
        $gameId = $created->id;

        $training = new TrainingSession($player, TrainingType::POINT, 7.5, 10);
        $this->em->persist($training);
        $shooting = new ShootingSession($player);
        $this->em->persist($shooting);
        $this->em->flush();
        $trainingId = (int) $training->getId();
        $shootingId = (int) $shooting->getId();
        $playerId = (int) $player->getId();
        $userId = (int) $user->getId();

        $issuer = static::getContainer()->get(AuthTokenIssuer::class);
        $issuer->issue($user, AuthTokenPurpose::PasswordReset, new \DateInterval('PT1H'));

        $token = $this->jwtEncoder->encode([
            'username' => $email,
            'sub' => (string) $userId,
        ]);

        $this->deletion->deleteAccount($token);
        $this->em->clear();

        self::assertNull($this->em->find(User::class, $userId));

        $keptPlayer = $this->em->find(Player::class, $playerId);
        self::assertNotNull($keptPlayer);
        self::assertNull($keptPlayer->getUser());

        $keptGame = $this->em->find(Game::class, $gameId);
        self::assertNotNull($keptGame);
        self::assertNull($keptGame->getCreatedBy());

        $participantCount = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM match_players WHERE match_id = ?',
            [$gameId],
        );
        self::assertSame(2, $participantCount);
        self::assertInstanceOf(GameParticipant::class, $this->em->getRepository(GameParticipant::class)->findOneBy([
            'game' => $keptGame,
            'player' => $keptPlayer,
        ]));

        self::assertNotNull($this->em->find(TrainingSession::class, $trainingId));
        self::assertNotNull($this->em->find(ShootingSession::class, $shootingId));

        $remainingTokens = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM auth_tokens WHERE user_id = ?',
            [$userId],
        );
        self::assertSame(0, $remainingTokens);
    }

    /**
     * @return array{0:string,1:User,2:User}
     */
    private function persistTwoUsers(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $userA = new User('a-'.$suffix.'@test.local');
        $userA->setPassword($this->hasher->hashPassword($userA, 'password123'));
        $userB = new User('b-'.$suffix.'@test.local');
        $userB->setPassword($this->hasher->hashPassword($userB, 'password123'));
        $this->em->persist($userA);
        $this->em->persist($userB);
        $this->em->flush();

        $tokenA = $this->jwtEncoder->encode([
            'username' => $userA->getEmail(),
            'sub' => (string) $userA->getId(),
        ]);

        return [$tokenA, $userA, $userB];
    }
}
