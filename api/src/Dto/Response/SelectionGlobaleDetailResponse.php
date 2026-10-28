<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class SelectionGlobaleDetailResponse
{
    /**
     * @param list<SelectionGlobaleCoachItemResponse> $coaches
     * @param list<PlayerItem> $players
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $coaches,
        public array $players,
    ) {
    }
}
