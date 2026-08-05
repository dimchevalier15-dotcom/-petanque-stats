<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class CreatePlayerResponse
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $nickname,
    ) {
    }
}
