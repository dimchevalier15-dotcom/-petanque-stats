<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class WebDatabaseTestCase extends WebTestCase
{
    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $server
     */
    protected function createDatabaseClient(array $options = [], array $server = []): KernelBrowser
    {
        return static::createClient($options, $server);
    }
}
