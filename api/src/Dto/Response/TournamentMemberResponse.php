<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TournamentMemberResponse
{
    public function __construct(
        public int $userId,
        public string $email,
        public bool $admin,
        public string $createdAt,
    ) {
    }
}
