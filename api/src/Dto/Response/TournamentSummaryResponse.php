<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class TournamentSummaryResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $status,
        public string $format,
        public ?string $startsAt,
        public ?string $endsAt,
    ) {
    }
}
