<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MeResponse
{
    public function __construct(
        public int $id,
        public string $email,
        public ?int $playerId,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $nickname,
        public bool $emailVerified = false,
    ) {
    }
}
