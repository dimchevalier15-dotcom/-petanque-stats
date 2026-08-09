<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class AuthSessionResponse
{
    public function __construct(
        public string $token,
        public MeResponse $user,
    ) {
    }
}
