<?php

declare(strict_types=1);

namespace App\Dto\Request;

final class UpdateUserPreferencesRequest
{
    public bool $requiresMatchValidation = false;
}
