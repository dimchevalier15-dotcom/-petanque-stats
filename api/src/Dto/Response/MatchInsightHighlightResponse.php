<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class MatchInsightHighlightResponse
{
    public function __construct(
        public string $type,
        public string $team,
        public ?string $bucket = null,
        public ?int $count = null,
        public ?int $total = null,
    ) {
    }
}
