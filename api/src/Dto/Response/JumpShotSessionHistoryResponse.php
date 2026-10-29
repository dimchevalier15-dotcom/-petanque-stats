<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class JumpShotSessionHistoryResponse
{
    /**
     * @param list<JumpShotSessionHistoryItemResponse> $items
     */
    public function __construct(
        public int $page,
        public int $pageSize,
        public int $total,
        public array $items,
    ) {
    }
}
