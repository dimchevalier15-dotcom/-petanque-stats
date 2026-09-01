<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\Support\MatchTestHelpers;
use App\Tests\Support\WebDatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class SharedMatchHttpTest extends WebDatabaseTestCase
{
    use MatchTestHelpers;

    public function testGetSharedMatchRecapIsPublic(): void
    {
        $client = $this->createDatabaseClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;
        $this->matchService = $container->get(\App\Service\MatchService::class);
        $this->recording = $container->get(\App\Service\MatchRecordingService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);

        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();
        $req = $this->baseCompleteRequest($playerAId, $playerBId);
        $ball = new \App\Dto\Request\CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [1];
        $ball->shotTypes = ['point'];
        $req->ends[0]->balls = [$ball];
        $response = $this->recording->complete($matchId, $req);

        self::assertNotNull($response->shareUuid);

        $client->request('GET', '/api/shared-matches/'.$response->shareUuid);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertSame($matchId, $payload['summary']['matchId']);
        self::assertSame(1, $payload['summary']['scoreA']);
        self::assertNull($payload['summary']['myMatchPlayerId'] ?? null);
        self::assertSame($matchId, $payload['context']['matchId']);
    }

    public function testGetSharedMatchRecapReturns404WhenUnknown(): void
    {
        $client = $this->createDatabaseClient();

        $client->request('GET', '/api/shared-matches/11111111-1111-4111-8111-111111111111');

        self::assertSame(404, $client->getResponse()->getStatusCode());
    }
}
