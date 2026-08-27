<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpsertLiveMatchRequest
{
    /**
     * @var array<string, mixed>
     */
    #[Assert\NotNull]
    #[Assert\Type('array')]
    public array $data;
}
