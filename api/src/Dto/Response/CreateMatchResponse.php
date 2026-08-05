<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class CreateMatchResponse
{
    public function __construct(public int $id)
    {
    }
}
