<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PendingValidationCountResponse
{
    public function __construct(
        public int $count,
    ) {
    }
}
