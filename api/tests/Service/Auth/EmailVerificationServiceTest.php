<?php

declare(strict_types=1);

namespace App\Tests\Service\Auth;

use App\Dto\Auth\RegisterInput;
use App\Entity\AuthToken;
use App\Entity\User;
use App\Enum\AuthTokenPurpose;
use App\Service\Auth\AuthTokenIssuer;
use App\Service\Auth\EmailVerificationService;
use App\Service\Auth\InvalidAuthTokenException;
use App\Service\Auth\RegistrationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Support\KernelDatabaseTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class EmailVerificationServiceTest extends KernelDatabaseTestCase
{
    private EntityManagerInterface $em;
    private AuthTokenIssuer $issuer;
    private EmailVerificationService $verification;
    private RegistrationService $registration;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->issuer = $container->get(AuthTokenIssuer::class);
        $this->verification = $container->get(EmailVerificationService::class);
        $this->registration = $container->get(RegistrationService::class);
    }

    public function testRegisterCreatesUnverifiedUserAndToken(): void
    {
        $email = 'verify-reg-'.bin2hex(random_bytes(4)).'@test.local';
        $input = $this->registerInput($email);

        $session = $this->registration->register($input);
        $user = $this->em->getRepository(User::class)->findOneByEmail($email);

        self::assertNotNull($user);
        self::assertFalse($user->isEmailVerified());
        self::assertFalse($session->user->emailVerified);
        self::assertNotSame('', $session->token);

        $tokens = $this->em->getRepository(AuthToken::class)->findBy([
            'user' => $user,
            'purpose' => AuthTokenPurpose::EmailVerification,
        ]);
        self::assertCount(1, $tokens);
    }

    public function testValidTokenVerifiesAccountOnce(): void
    {
        $user = $this->createUser();
        $plain = $this->issuer->issue($user, AuthTokenPurpose::EmailVerification, new \DateInterval('PT24H'));

        self::assertSame('verified', $this->verification->verify($plain));
        $this->em->refresh($user);
        self::assertTrue($user->isEmailVerified());
        self::assertSame('invalid', $this->verification->verify($plain));
    }

    public function testExpiredTokenCannotBeUsed(): void
    {
        $user = $this->createUser();
        $plain = $this->issuer->issue($user, AuthTokenPurpose::EmailVerification, new \DateInterval('PT24H'));
        $token = $this->em->getRepository(AuthToken::class)->findOneByHash(hash('sha256', $plain));
        self::assertNotNull($token);
        $token->setExpiresAt(new DateTimeImmutable('-1 minute'));
        $this->em->flush();

        self::assertSame('invalid', $this->verification->verify($plain));
        $this->em->refresh($user);
        self::assertFalse($user->isEmailVerified());
    }

    public function testInvalidTokenIsRejected(): void
    {
        self::assertSame('invalid', $this->verification->verify('not-a-valid-token'));
        self::assertSame('invalid', $this->verification->verify(str_repeat('ab', 32)));
    }

    public function testAlreadyVerifiedAccount(): void
    {
        $user = $this->createUser();
        $user->markEmailVerified(new DateTimeImmutable());
        $this->em->flush();

        $plain = $this->issuer->issue($user, AuthTokenPurpose::EmailVerification, new \DateInterval('PT24H'));
        self::assertSame('already_verified', $this->verification->verify($plain));

        $this->verification->sendForUser($user);
        $open = $this->em->getRepository(AuthToken::class)->findBy([
            'user' => $user,
            'purpose' => AuthTokenPurpose::EmailVerification,
            'usedAt' => null,
        ]);
        self::assertCount(0, $open);
    }

    public function testResendInvalidatesPreviousToken(): void
    {
        $user = $this->createUser();
        $first = $this->issuer->issue($user, AuthTokenPurpose::EmailVerification, new \DateInterval('PT24H'));
        $this->verification->sendForUser($user);

        self::assertSame('invalid', $this->verification->verify($first));

        $tokens = $this->em->getRepository(AuthToken::class)->findBy([
            'user' => $user,
            'purpose' => AuthTokenPurpose::EmailVerification,
            'usedAt' => null,
        ]);
        self::assertCount(1, $tokens);
    }

    public function testPasswordResetTokenCannotVerifyEmail(): void
    {
        $user = $this->createUser();
        $plain = $this->issuer->issue($user, AuthTokenPurpose::PasswordReset, new \DateInterval('PT1H'));

        self::assertSame('invalid', $this->verification->verify($plain));
        $this->em->refresh($user);
        self::assertFalse($user->isEmailVerified());
    }

    public function testIssuerConsumeRejectsReusedToken(): void
    {
        $user = $this->createUser();
        $plain = $this->issuer->issue($user, AuthTokenPurpose::EmailVerification, new \DateInterval('PT24H'));
        $this->issuer->consume($plain, AuthTokenPurpose::EmailVerification);

        $this->expectException(InvalidAuthTokenException::class);
        $this->issuer->consume($plain, AuthTokenPurpose::EmailVerification);
    }

    private function createUser(): User
    {
        $user = new User('verify-'.bin2hex(random_bytes(4)).'@test.local');
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function registerInput(string $email): RegisterInput
    {
        $input = new RegisterInput();
        $input->email = $email;
        $input->password = 'password123';
        $input->firstName = 'Jean';
        $input->lastName = 'Dupont';
        $input->nickname = 'Jeannot';

        return $input;
    }
}
