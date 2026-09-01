<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\Support\WebDatabaseTestCase;

final class ConfigHttpTest extends WebDatabaseTestCase
{
    public function testGetConfigIsPublic(): void
    {
        $client = $this->createDatabaseClient();

        $client->request('GET', '/api/config');

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertSame('1.4.1', $payload['latestVersion']);
        self::assertSame('1.0.0', $payload['minimumVersion']);
        self::assertSame(
            'https://play.google.com/store/apps/details?id=com.petanquestats.app',
            $payload['androidStoreUrl'],
        );
    }
}
