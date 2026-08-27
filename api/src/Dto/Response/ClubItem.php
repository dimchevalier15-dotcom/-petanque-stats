<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ClubItem
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public CountryItem $country,
    ) {
    }
}
