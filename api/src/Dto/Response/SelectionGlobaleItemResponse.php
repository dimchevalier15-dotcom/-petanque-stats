<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class SelectionGlobaleItemResponse
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
