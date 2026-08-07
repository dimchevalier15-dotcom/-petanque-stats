<?php

declare(strict_types=1);

namespace App\Dto\Response;

/**
 * Paginated match history for the current player.
 */
final class MatchHistoryResponse
{
    /** @param list<MatchHistoryItemResponse> $items */
    public function __construct(
        public int $page,
        public int $pageSize,
        public int $total,
        public array $items,
    ) {
    }
}
