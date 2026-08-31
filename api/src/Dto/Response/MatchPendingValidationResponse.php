<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchPendingValidationResponse
{
    /** @param list<MatchPendingValidationItemResponse> $items */
    public function __construct(
        public int $total,
        public array $items,
    ) {
    }
}
