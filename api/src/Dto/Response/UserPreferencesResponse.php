<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class UserPreferencesResponse
{
    public function __construct(
        public bool $requiresMatchValidation,
    ) {
    }
}
