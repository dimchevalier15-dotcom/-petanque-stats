<?php

declare(strict_types=1);

namespace App\Service;

final class PlayerViewContext
{
    public function __construct(
        public ?int $playerId,
        public ?int $historyUserId,
        public ?string $displayName,
    ) {
    }
}
