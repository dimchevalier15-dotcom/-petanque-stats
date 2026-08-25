<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class CompetitionItem
{
    public function __construct(
        public int $id,
        public string $name,
        public string $eventDate,
        public string $country,
        public ?string $context,
    ) {
    }
}
