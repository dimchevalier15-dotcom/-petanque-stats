<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PlayerHttpTest extends WebTestCase
{
    public function testPlayerEndpointsRequireAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/players?q=Jean');
        self::assertSame(401, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/players/1');
        self::assertSame(401, $client->getResponse()->getStatusCode());

        $client->request(
            'POST',
            '/api/players',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'nickname' => 'Jeannot',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testUnlinkedPlayerSearchRemainsPublicForRegistration(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/auth/unlinked-players/search?q=Jean');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testAuthenticatedUserCanCreateAndSearchPlayers(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $jwtEncoder = static::getContainer()->get(JWTEncoderInterface::class);

        $suffix = bin2hex(random_bytes(4));
        $user = new User('player-http-'.$suffix.'@test.local');
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        $token = $jwtEncoder->encode([
            'username' => $user->getEmail(),
            'sub' => (string) $user->getId(),
        ]);

        $this->requestWithBearer($client, 'POST', '/api/players', $token, [
            'firstName' => 'Alice',
            'lastName' => 'Test'.$suffix,
            'nickname' => 'Ali'.$suffix,
        ]);
        self::assertSame(201, $client->getResponse()->getStatusCode());
        $created = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertSame('Alice', $created['firstName']);

        $client->request('GET', '/api/players?q=Alice', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $results = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertNotEmpty($results);

        $client->request('GET', '/api/players/'.$created['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requestWithBearer(KernelBrowser $client, string $method, string $uri, string $token, ?array $payload = null): void
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ];
        $content = $payload !== null ? json_encode($payload, JSON_THROW_ON_ERROR) : null;
        $client->request($method, $uri, server: $server, content: $content);
    }
}
