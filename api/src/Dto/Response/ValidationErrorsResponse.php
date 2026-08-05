<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ValidationErrorsResponse
{
    /** @param array<string, string> $errors */
    public function __construct(public array $errors)
    {
    }
}
