<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlaceholderPlayersResponse
{
    /**
     * @param list<int> $playerIds Placeholder ids in A–F order.
     */
    public function __construct(
        public array $playerIds,
    ) {
    }
}
