<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class CountryItem
{
    public function __construct(
        public int $id,
        public string $isoCode,
        public string $name,
    ) {
    }
}
