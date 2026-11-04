<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\RingsTarget;
use Symfony\Component\Validator\Constraints as Assert;

final class RingsThrowInputDto
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [RingsTarget::class, 'values'])]
    public ?string $target = null;

    #[Assert\NotNull]
    public ?bool $success = null;
}
