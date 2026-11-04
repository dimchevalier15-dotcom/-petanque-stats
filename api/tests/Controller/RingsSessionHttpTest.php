<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Player;
use App\Entity\User;
use App\Tests\Support\WebDatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class RingsSessionHttpTest extends WebDatabaseTestCase
{
    public function testShootingSessionWithCarreauxFlowsThroughTheApi(): void
    {
        $client = $this->createDatabaseClient();
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer '.$this->createUserWithLinkedPlayer()];

        $started = $this->json($client, 'POST', '/api/rings-sessions', $auth, ['mode' => 'shooting']);
        self::assertSame('shooting', $started['mode']);

        $current = $this->json($client, 'GET', '/api/rings-sessions/current?mode=shooting', $auth);
        self::assertSame($started['id'], $current['id']);
        self::assertNull($this->json($client, 'GET', '/api/rings-sessions/current', $auth));

        $throws = [
            ['target' => 'far', 'success' => true, 'carreau' => true],
            ['target' => 'middle', 'success' => true],
            ...array_fill(0, 13, ['target' => 'near', 'success' => false]),
        ];
        $summary = $this->json($client, 'POST', '/api/rings-sessions/'.$started['id'].'/complete', $auth, ['throws' => $throws]);
        self::assertSame(13, $summary['totalScore']);
        self::assertTrue($summary['throws'][0]['carreau']);
        self::assertSame(10, $summary['throws'][0]['score']);
        self::assertFalse($summary['throws'][1]['carreau']);

        $history = $this->json($client, 'GET', '/api/rings-sessions/history?mode=shooting', $auth);
        self::assertSame(1, $history['total']);
        self::assertSame(0, $this->json($client, 'GET', '/api/rings-sessions/history', $auth)['total']);

        $stats = $this->json($client, 'GET', '/api/rings-sessions/stats?mode=shooting', $auth);
        self::assertSame('ok', $stats['status']);
        self::assertSame(150, $stats['summary']['maxSessionScore']);
        self::assertSame(1, $stats['summary']['carreauCount']);
        self::assertSame(1, $stats['byTarget'][0]['carreauCount']);
    }

    public function testCarreauIsRejectedForAPointSessionAndUnknownModeIsRejected(): void
    {
        $client = $this->createDatabaseClient();
        $auth = ['HTTP_AUTHORIZATION' => 'Bearer '.$this->createUserWithLinkedPlayer()];

        $started = $this->json($client, 'POST', '/api/rings-sessions', $auth, ['mode' => 'point']);
        $throws = [
            ['target' => 'far', 'success' => true, 'carreau' => true],
            ...array_fill(0, 14, ['target' => 'near', 'success' => false]),
        ];
        $client->request(
            'POST',
            '/api/rings-sessions/'.$started['id'].'/complete',
            server: $auth + ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['throws' => $throws], JSON_THROW_ON_ERROR),
        );
        self::assertSame(400, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/rings-sessions/stats?mode=bogus', server: $auth);
        self::assertSame(400, $client->getResponse()->getStatusCode());
    }

    /**
     * @param array<string, string>     $server
     * @param array<string, mixed>|null $body
     */
    private function json(KernelBrowser $client, string $method, string $uri, array $server, ?array $body = null): mixed
    {
        $client->request(
            $method,
            $uri,
            server: $server + ['CONTENT_TYPE' => 'application/json'],
            content: $body !== null ? json_encode($body, JSON_THROW_ON_ERROR) : null,
        );
        $response = $client->getResponse();
        self::assertLessThan(300, $response->getStatusCode(), (string) $response->getContent());

        return json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    private function createUserWithLinkedPlayer(): string
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $jwtEncoder = static::getContainer()->get(JWTEncoderInterface::class);

        $email = sprintf('rings-http-%s@test.local', bin2hex(random_bytes(4)));
        $user = new User($email);
        $em->persist($user);
        $em->flush();

        $player = new Player('Jean', 'Bernard', 'Jeannot');
        $player->setUser($user);
        $em->persist($player);
        $em->flush();

        return $jwtEncoder->encode(['username' => $email, 'sub' => (string) $user->getId()]);
    }
}
