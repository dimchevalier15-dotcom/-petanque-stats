<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class AppVersionConfigResponse
{
    public function __construct(
        public string $latestVersion,
        public string $minimumVersion,
        public string $androidStoreUrl,
    ) {
    }
}
