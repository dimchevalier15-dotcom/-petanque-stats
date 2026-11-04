<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class RingsSessionStartedResponse
{
    public function __construct(
        public int $id,
        public string $mode,
        public string $createdAt,
    ) {
    }
}
