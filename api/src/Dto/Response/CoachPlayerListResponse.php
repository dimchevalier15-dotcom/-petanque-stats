<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class CoachPlayerListResponse
{
    /**
     * @param list<CoachPlayerListItemResponse> $items
     */
    public function __construct(
        public int $clubId,
        public string $clubName,
        public ?string $from,
        public ?string $to,
        public array $items,
    ) {
    }
}
