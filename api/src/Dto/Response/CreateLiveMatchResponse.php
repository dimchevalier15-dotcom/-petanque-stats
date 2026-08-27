<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class CreateLiveMatchResponse
{
    public function __construct(
        public string $uuid,
        public string $url,
    ) {
    }
}
