<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class SelectionGlobaleCoachItemResponse
{
    public function __construct(
        public int $userId,
        public string $email,
    ) {
    }
}
