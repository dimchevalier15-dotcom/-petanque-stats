<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerMergeResultResponse
{
    public function __construct(
        public int $playerId,
        public ?int $userId,
    ) {
    }
}
