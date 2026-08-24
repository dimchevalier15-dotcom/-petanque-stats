<?php

declare(strict_types=1);

namespace App\Tests\Service\Account;

use App\Entity\Player;
use App\Entity\User;
use App\Enum\AuthTokenPurpose;
use App\Service\Account\AccountDeletionService;
use App\Service\Auth\AuthTokenIssuer;
use App\Service\Auth\InvalidTokenException;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccountDeletionServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private AccountDeletionService $deletion;
    private JWTEncoderInterface $jwtEncoder;
    private UserPasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->deletion = static::getContainer()->get(AccountDeletionService::class);
        $this->jwtEncoder = static::getContainer()->get(JWTEncoderInterface::class);
        $this->hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testDeleteAccountRemovesUserAndUnlinksPlayer(): void
    {
        $email = 'delete-'.bin2hex(random_bytes(4)).'@test.local';
        $user = new User($email);
        $user->setPassword($this->hasher->hashPassword($user, 'password123'));
        $this->em->persist($user);
        $this->em->flush();

        $player = new Player('Jean', 'Compte', 'JC');
        $player->setUser($user);
        $this->em->persist($player);
        $this->em->flush();
        $playerId = (int) $player->getId();
        $userId = (int) $user->getId();

        $issuer = static::getContainer()->get(AuthTokenIssuer::class);
        $issuer->issue($user, AuthTokenPurpose::EmailVerification, new \DateInterval('PT1H'));

        $token = $this->jwtEncoder->encode([
            'username' => $email,
            'sub' => (string) $userId,
        ]);

        $this->deletion->deleteAccount($token);

        self::assertNull($this->em->find(User::class, $userId));
        $keptPlayer = $this->em->find(Player::class, $playerId);
        self::assertNotNull($keptPlayer);
        self::assertNull($keptPlayer->getUser());
        $remainingTokens = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM auth_tokens WHERE user_id = ?',
            [$userId],
        );
        self::assertSame(0, $remainingTokens);
    }

    public function testDeleteAccountRejectsInvalidToken(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->deletion->deleteAccount('not-a-jwt');
    }
}
