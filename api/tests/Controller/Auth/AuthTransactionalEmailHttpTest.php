<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Entity\User;
use App\Enum\AuthTokenPurpose;
use App\Service\Auth\AuthTokenIssuer;
use App\Service\Auth\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Support\WebDatabaseTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthTransactionalEmailHttpTest extends WebDatabaseTestCase
{
    public function testForgotPasswordExistingAndUnknownEmailsHaveIdenticalResponses(): void
    {
        $client = $this->createDatabaseClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User('http-reset-'.bin2hex(random_bytes(4)).'@test.local');
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        $known = $this->postJson($client, '/api/auth/forgot-password', ['email' => $user->getEmail()]);
        $unknown = $this->postJson($client, '/api/auth/forgot-password', [
            'email' => 'missing-'.bin2hex(random_bytes(4)).'@test.local',
        ]);

        self::assertSame(200, $known['status']);
        self::assertSame($known['status'], $unknown['status']);
        self::assertSame($known['content'], $unknown['content']);
        self::assertSame(PasswordResetService::GENERIC_REQUEST_MESSAGE, $known['json']['message'] ?? null);
        self::assertArrayNotHasKey('token', $known['json']);
    }

    public function testResetPasswordHttpSuccessDoesNotReturnToken(): void
    {
        $client = $this->createDatabaseClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $issuer = static::getContainer()->get(AuthTokenIssuer::class);

        $user = new User('http-reset-ok-'.bin2hex(random_bytes(4)).'@test.local');
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();
        $plain = $issuer->issue($user, AuthTokenPurpose::PasswordReset, new \DateInterval('PT1H'));

        $result = $this->postJson($client, '/api/auth/reset-password', [
            'token' => $plain,
            'password' => 'new-password-9',
        ]);

        self::assertSame(200, $result['status']);
        self::assertArrayNotHasKey('token', $result['json']);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status:int,content:string,json:array<string,mixed>}
     */
    private function postJson(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $uri, array $payload): array
    {
        $client->request(
            'POST',
            $uri,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
        $content = (string) $client->getResponse()->getContent();
        /** @var array<string, mixed> $json */
        $json = json_decode($content, true) ?: [];

        return [
            'status' => $client->getResponse()->getStatusCode(),
            'content' => $content,
            'json' => $json,
        ];
    }
}
