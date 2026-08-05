<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class ErrorMessageResponse
{
    public function __construct(public string $message)
    {
    }
}
