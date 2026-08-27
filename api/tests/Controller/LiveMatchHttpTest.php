<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\LiveMatch;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LiveMatchHttpTest extends WebTestCase
{
    public function testGetLiveMatchIsPublic(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $liveMatch = new LiveMatch('11111111-1111-4111-8111-111111111111', ['scoreA' => 0]);
        $em->persist($liveMatch);
        $em->flush();

        $client->request('GET', '/api/live-matches/11111111-1111-4111-8111-111111111111');

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertSame('11111111-1111-4111-8111-111111111111', $payload['uuid']);
        self::assertSame('active', $payload['status']);
        self::assertSame(['scoreA' => 0], $payload['data']);
    }

    public function testCreateUpdateAndFinishRequireAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/live-matches', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['data' => ['scoreA' => 0]], JSON_THROW_ON_ERROR));
        self::assertSame(401, $client->getResponse()->getStatusCode());

        $client->request('PUT', '/api/live-matches/11111111-1111-4111-8111-111111111111', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['data' => ['scoreA' => 1]], JSON_THROW_ON_ERROR));
        self::assertSame(401, $client->getResponse()->getStatusCode());

        $client->request('POST', '/api/live-matches/11111111-1111-4111-8111-111111111111/finish');
        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testAuthenticatedLiveMatchLifecycle(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $jwtEncoder = static::getContainer()->get(JWTEncoderInterface::class);

        $suffix = bin2hex(random_bytes(4));
        $user = new User('live-user-'.$suffix.'@test.local');
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        $token = $jwtEncoder->encode([
            'username' => $user->getEmail(),
            'sub' => (string) $user->getId(),
        ]);

        $this->requestWithBearer($client, 'POST', '/api/live-matches', $token, ['data' => ['scoreA' => 0, 'scoreB' => 0]]);
        self::assertSame(201, $client->getResponse()->getStatusCode());
        $created = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertArrayHasKey('uuid', $created);
        self::assertArrayHasKey('url', $created);
        self::assertStringContainsString('/live/', $created['url']);

        $uuid = $created['uuid'];

        $client->request('GET', '/api/live-matches/'.$uuid);
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $this->requestWithBearer($client, 'PUT', '/api/live-matches/'.$uuid, $token, ['data' => ['scoreA' => 5, 'scoreB' => 3]]);
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $updated = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertSame(5, $updated['data']['scoreA']);

        $this->requestWithBearer($client, 'POST', '/api/live-matches/'.$uuid.'/finish', $token);
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $finished = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertSame('finished', $finished['status']);
        self::assertNotNull($finished['finishedAt']);
        self::assertSame(5, $finished['data']['scoreA']);

        $client->request('GET', '/api/live-matches/'.$uuid);
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $public = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertSame('finished', $public['status']);

        $this->requestWithBearer($client, 'PUT', '/api/live-matches/'.$uuid, $token, ['data' => ['scoreA' => 6, 'scoreB' => 3]]);
        self::assertSame(409, $client->getResponse()->getStatusCode());
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
