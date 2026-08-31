<?php

declare(strict_types=1);

namespace App\Tests\Controller\Account;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Tests\Support\WebDatabaseTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccountDeletionHttpTest extends WebDatabaseTestCase
{
    public function testDeleteAccountRequiresAuthentication(): void
    {
        $client = $this->createDatabaseClient();
        $client->request('DELETE', '/api/account');

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testDeleteAccountRemovesOnlyTheAuthenticatedUserAndInvalidatesTheToken(): void
    {
        $client = $this->createDatabaseClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $jwtEncoder = static::getContainer()->get(JWTEncoderInterface::class);

        $suffix = bin2hex(random_bytes(4));
        $userA = new User('http-del-a-'.$suffix.'@test.local');
        $userA->setPassword($hasher->hashPassword($userA, 'password123'));
        $userB = new User('http-del-b-'.$suffix.'@test.local');
        $userB->setPassword($hasher->hashPassword($userB, 'password123'));
        $em->persist($userA);
        $em->persist($userB);
        $em->flush();
        $userAId = (int) $userA->getId();
        $userBId = (int) $userB->getId();

        $tokenA = $jwtEncoder->encode([
            'username' => $userA->getEmail(),
            'sub' => (string) $userAId,
        ]);
        $tokenB = $jwtEncoder->encode([
            'username' => $userB->getEmail(),
            'sub' => (string) $userBId,
        ]);

        $this->requestWithBearer($client, 'DELETE', '/api/account', $tokenA);
        self::assertSame(204, $client->getResponse()->getStatusCode());

        $em->clear();
        self::assertNull($em->find(User::class, $userAId));
        self::assertNotNull($em->find(User::class, $userBId));

        $this->requestWithBearer($client, 'GET', '/api/auth/me', $tokenA);
        self::assertSame(401, $client->getResponse()->getStatusCode());

        $this->requestWithBearer($client, 'GET', '/api/auth/me', $tokenB);
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testUserBTokenCannotDeleteUserA(): void
    {
        $client = $this->createDatabaseClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $jwtEncoder = static::getContainer()->get(JWTEncoderInterface::class);

        $suffix = bin2hex(random_bytes(4));
        $userA = new User('http-keep-a-'.$suffix.'@test.local');
        $userA->setPassword($hasher->hashPassword($userA, 'password123'));
        $userB = new User('http-keep-b-'.$suffix.'@test.local');
        $userB->setPassword($hasher->hashPassword($userB, 'password123'));
        $em->persist($userA);
        $em->persist($userB);
        $em->flush();
        $userAId = (int) $userA->getId();
        $userBId = (int) $userB->getId();

        $tokenB = $jwtEncoder->encode([
            'username' => $userB->getEmail(),
            'sub' => (string) $userBId,
        ]);

        $this->requestWithBearer($client, 'DELETE', '/api/account', $tokenB);
        self::assertSame(204, $client->getResponse()->getStatusCode());

        $em->clear();
        self::assertNotNull($em->find(User::class, $userAId));
        self::assertNull($em->find(User::class, $userBId));
    }

    private function requestWithBearer(KernelBrowser $client, string $method, string $uri, string $token): void
    {
        $client->request($method, $uri, server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);
    }
}
