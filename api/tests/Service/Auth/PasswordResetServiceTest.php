<?php

declare(strict_types=1);

namespace App\Tests\Service\Auth;

use App\Entity\User;
use App\Enum\AuthTokenPurpose;
use App\Service\Auth\AuthTokenIssuer;
use App\Service\Auth\InvalidAuthTokenException;
use App\Service\Auth\PasswordResetService;
use App\Service\Auth\RegistrationValidationException;
use App\Dto\Request\ResetPasswordRequest;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Support\KernelDatabaseTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetServiceTest extends KernelDatabaseTestCase
{
    private EntityManagerInterface $em;
    private AuthTokenIssuer $issuer;
    private PasswordResetService $reset;
    private UserPasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->issuer = static::getContainer()->get(AuthTokenIssuer::class);
        $this->reset = static::getContainer()->get(PasswordResetService::class);
        $this->hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testRequestResetCreatesTokenForExistingUserOnly(): void
    {
        $user = $this->createUser();
        $this->reset->requestReset($user->getEmail(), '127.0.0.1');
        $this->reset->requestReset('nobody-'.bin2hex(random_bytes(4)).'@test.local', '127.0.0.1');

        $tokens = $this->em->getRepository(\App\Entity\AuthToken::class)->findBy([
            'user' => $user,
            'purpose' => AuthTokenPurpose::PasswordReset,
        ]);
        self::assertCount(1, $tokens);
    }

    public function testSuccessfulResetChangesPasswordAndCannotBeReused(): void
    {
        $user = $this->createUser('reset-ok-'.bin2hex(random_bytes(4)).'@test.local', 'old-password');
        $plain = $this->issuer->issue($user, AuthTokenPurpose::PasswordReset, new \DateInterval('PT1H'));

        $input = new ResetPasswordRequest();
        $input->token = $plain;
        $input->password = 'new-password-9';
        $this->reset->resetPassword($input);

        $this->em->refresh($user);
        self::assertTrue($this->hasher->isPasswordValid($user, 'new-password-9'));
        self::assertFalse($this->hasher->isPasswordValid($user, 'old-password'));

        $again = new ResetPasswordRequest();
        $again->token = $plain;
        $again->password = 'another-password';
        $this->expectException(InvalidAuthTokenException::class);
        $this->reset->resetPassword($again);
    }

    public function testExpiredResetTokenIsRejected(): void
    {
        $user = $this->createUser();
        $plain = $this->issuer->issue($user, AuthTokenPurpose::PasswordReset, new \DateInterval('PT1H'));
        $token = $this->em->getRepository(\App\Entity\AuthToken::class)->findOneByHash(hash('sha256', $plain));
        self::assertNotNull($token);
        $token->setExpiresAt(new DateTimeImmutable('-1 minute'));
        $this->em->flush();

        $input = new ResetPasswordRequest();
        $input->token = $plain;
        $input->password = 'new-password-9';

        try {
            $this->reset->resetPassword($input);
            self::fail('Expired token should be rejected');
        } catch (InvalidAuthTokenException) {
        }

        $this->em->refresh($user);
        self::assertTrue($this->hasher->isPasswordValid($user, 'password123'));
    }

    public function testInvalidResetTokenIsRejected(): void
    {
        $input = new ResetPasswordRequest();
        $input->token = str_repeat('cd', 32);
        $input->password = 'new-password-9';

        $this->expectException(InvalidAuthTokenException::class);
        $this->reset->resetPassword($input);
    }

    public function testNewResetTokenInvalidatesPreviousOne(): void
    {
        $user = $this->createUser();
        $first = $this->issuer->issue($user, AuthTokenPurpose::PasswordReset, new \DateInterval('PT1H'));
        $second = $this->issuer->issue($user, AuthTokenPurpose::PasswordReset, new \DateInterval('PT1H'));

        $input = new ResetPasswordRequest();
        $input->token = $first;
        $input->password = 'new-password-9';
        try {
            $this->reset->resetPassword($input);
            self::fail('First token should have been invalidated');
        } catch (InvalidAuthTokenException) {
        }

        $input->token = $second;
        $this->reset->resetPassword($input);
        $this->em->refresh($user);
        self::assertTrue($this->hasher->isPasswordValid($user, 'new-password-9'));
    }

    public function testResetPasswordRejectsShortPassword(): void
    {
        $user = $this->createUser();
        $plain = $this->issuer->issue($user, AuthTokenPurpose::PasswordReset, new \DateInterval('PT1H'));
        $input = new ResetPasswordRequest();
        $input->token = $plain;
        $input->password = 'short';

        $this->expectException(RegistrationValidationException::class);
        $this->reset->resetPassword($input);
    }

    private function createUser(?string $email = null, string $password = 'password123'): User
    {
        $user = new User($email ?? 'reset-'.bin2hex(random_bytes(4)).'@test.local');
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
